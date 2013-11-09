<?php
/**
 * Updated by Brad Walker on 6/4/13 at 5:11 PM
 */

namespace FREST\Request;

use FREST;
use FREST\Type;
use FREST\Result;
use FREST\Setting;
use FREST\Spec;

/**
 * Class Update
 * @package FREST\Request
 */
class Update extends Request {

	/** @var array */
	protected $updateSpecs;
	
	/** @var array */
	protected $tableUpdateSpecs;

	/**
	 * @param FREST\Resource $resource
	 * @throws FREST\Exception
	 */
	public function setupWithResource($resource) {
		parent::setupWithResource($resource);

		if (!isset($this->resourceID)) {
			throw new FREST\Exception(FREST\Exception::MissingResourceID);
		}

		if (!isset($this->parameters) || count($this->parameters) == 0) {
			throw new FREST\Exception(FREST\Exception::NothingToDo, "No update parameters specified");
		}
		
		$this->updateSpecs = $this->generateUpdateSpecs($this->resource);
		$this->tableUpdateSpecs = $this->generateTableUpdateSpecs($this->resource, $this->updateSpecs);
	}

	/**
	 * @param bool $forceRegen
	 * @return Result\Update
	 * @throws FREST\Exception
	 */
	public function generateResult($forceRegen = FALSE) {
		$this->frest->startTimingForLabel(Type\Timing::PROCESSING, 'update');

		$otherResult = parent::generateResult($forceRegen);
		if (isset($otherResult)) {
			return $otherResult;
		}

		$pdo = $this->frest->getConfig()->getPDO();

		$isPerformingTransaction = FALSE;

		if (count($this->tableUpdateSpecs) > 1) {
			$pdo->beginTransaction();
			$isPerformingTransaction = TRUE;
		}

		/** @var Setting\Field $idFieldSetting */
		$this->resource->getIDField($idFieldSetting); // populates

		$this->frest->stopTimingForLabel(Type\Timing::PROCESSING, 'update');

		$i = 0;
		/** @var Spec\TableUpdate $tableUpdateSpec */
		foreach ($this->tableUpdateSpecs as $tableUpdateSpec) {
			$this->frest->startTimingForLabel(Type\Timing::PROCESSING, 'update');

			$table = $tableUpdateSpec->getTable();
			$queryParameterSpecs = $tableUpdateSpec->getQueryParameterSpecs();

			$idFieldName = $this->resource->getIDFieldForTable($table);

			$assignmentStringList = $this->generateAssignmentStringList($queryParameterSpecs);
			$assignmentString = implode(',', $assignmentStringList);
			
			$this->frest->stopTimingForLabel(Type\Timing::PROCESSING, 'update');
			$this->frest->startTimingForLabel(Type\Timing::SQL, 'update');

			$sql = "UPDATE {$table} SET {$assignmentString} WHERE {$idFieldName} = :_id";

			$updateStmt = $pdo->prepare($sql);
			
			$updateStmt->bindValue(
				':_id',
				$this->resourceID,
				Type\Variable::pdoTypeFromVariableType($idFieldSetting->getVariableType())
			);

			/** @var Spec\QueryParameter $queryParameterSpec */
			foreach ($queryParameterSpecs as $queryParameterSpec) {
				$updateStmt->bindValue(
					$queryParameterSpec->getParameterName(),
					$queryParameterSpec->getValue(),
					Type\Variable::pdoTypeFromVariableType($queryParameterSpec->getVariableType())
				);
			}

			if (!$updateStmt->execute()) {
				if ($isPerformingTransaction) {
					$pdo->rollBack();
				}

				throw new FREST\Exception(FREST\Exception::SQLError, 'Error updating database');
			}

			$this->frest->stopTimingForLabel(Type\Timing::SQL, 'update');

			$i++;
		}

		$this->frest->startTimingForLabel(Type\Timing::SQL, 'update');

		if ($isPerformingTransaction) {
			$pdo->commit();
		}

		$this->frest->stopTimingForLabel(Type\Timing::SQL, 'update');

		$this->result = new Result\Update();

		return $this->result;
	}



	/**
	 * @param FREST\Resource $resource
	 * @return array
	 * @throws FREST\Exception
	 */
	private function generateUpdateSpecs($resource) {
		$updateSpecs = array();

		$updateSettings = $resource->getUpdateSettings();
		
		/** @var Setting\Update $updateSetting */
		foreach ($updateSettings as $updateSetting) {
			$alias = $updateSetting->getAlias();

			if (isset($this->parameters[$alias])) {
				$value = $this->parameters[$alias];

				$field = $resource->getFieldForAlias($alias);
				$fieldSetting = $resource->getFieldSettingForAlias($alias);
				$variableType = $fieldSetting->getVariableType();

				// Type checking
				$castedValue = Type\Variable::castValue($value, $variableType);
				if (!isset($castedValue)) {
					$typeString = Type\Variable::getString($variableType);
					throw new FREST\Exception(FREST\Exception::InvalidType, "Expecting '{$alias}' to be of type '{$typeString}' but received '{$value}'");
				}

				// Condition Func
				$conditionFunction = $updateSetting->getConditionFunction();
				if (isset($conditionFunction)) {
					if (!method_exists($resource, $conditionFunction)) {
						$resourceClassName = get_class($resource);
						throw new FREST\Exception(FREST\Exception::ConditionFunctionMissing, "Function name: '{$conditionFunction}', resource: '{$resourceClassName}'");
					}

					$isValueValid = $resource->$conditionFunction($castedValue);
					if (!$isValueValid) {
						throw new FREST\Exception(FREST\Exception::InvalidFieldValue, "Field: '{$alias}'");
					}
				}

				// Filter Func
				$filterFunction = $updateSetting->getFilterFunction();
				if (isset($filterFunction)) {
					if (!method_exists($resource, $filterFunction)) {
						$resourceClassName = get_class($resource);
						throw new FREST\Exception(FREST\Exception::FilterFunctionMissing, "Function name: '{$filterFunction}', resource: '{$resourceClassName}'");
					}

					$castedValue = $resource->$filterFunction($castedValue);
				}

				$updateSpec = new Spec\Update(
					$alias,
					$field,
					$castedValue,
					$variableType
				);

				$updateSpecs[$alias] = $updateSpec;
			}
		}

		return $updateSpecs;
	}


	/**
	 * @param FREST\Resource $resource
	 * @param array $updateSpecs
	 * @return array|NULL
	 */
	protected function generateTableUpdateSpecs($resource, $updateSpecs) {
		$tableUpdateSpecs = array();

		$tablesAndTheirUpdateSpecs = array();

		/** @var Spec\Update $updateSpec */
		foreach ($updateSpecs as $updateSpec) {
			$alias = $updateSpec->getAlias();
			$field = $resource->getFieldForAlias($alias);
			$table = $resource->getTableForField($field);

			$tablesAndTheirUpdateSpecs[$table][] = $updateSpec;
		}

		foreach ($tablesAndTheirUpdateSpecs as $table=>$updateSpecs) {
			$tableUpdateSpec = new Spec\TableUpdate(
				$table,
				$updateSpecs
			);

			$tableUpdateSpecs[] = $tableUpdateSpec;
		}

		return $tableUpdateSpecs;
	}


	/**
	 * @param array $queryParameterSpecs
	 * @return array
	 */
	private function generateAssignmentStringList($queryParameterSpecs) {
		$assignmentStringList = array();
		
		/** @var Spec\QueryParameter $queryParameterSpec */
		foreach ($queryParameterSpecs as $queryParameterSpec) {
			$field = $queryParameterSpec->getField();
			$parameterName = $queryParameterSpec->getParameterName();
			
			$assignmentString = "{$field}={$parameterName}";

			$assignmentStringList[] = $assignmentString;
		}

		return $assignmentStringList;
	}


	/**
	 * @param $parameter
	 * @param $value
	 * @return bool
	 * @throws FREST\Exception
	 */
	protected function isValidURLParameter($parameter, $value) {
		/** @noinspection PhpUndefinedClassInspection */
		$isValid = parent::isValidURLParameter($parameter, $value);

		if (!$isValid) { // if not already determined to be valid
			$updateSettings = $this->resource->getUpdateSettings();

			if (isset($updateSettings[$parameter])) {
				/** @var Setting\Update $updateSetting */
				$updateSetting = $updateSettings[$parameter];

				$fieldSetting = $this->resource->getFieldSettingForAlias($updateSetting->getAlias());
				if (!isset($fieldSetting)) {
					$resourceName = get_class($this->resource);
					throw new FREST\Exception(FREST\Exception::Config, "No field setting found for condition '{$parameter}' in resource {$resourceName}");
				}

				$isValid = TRUE;
			}
		}

		return $isValid;
	}
}