<?php
/**
 * Updated by Brad Walker on 6/4/13 at 5:11 PM
 */

namespace FREST\Request;

use FREST;
use FREST\Enum;
use FREST\Result;
use FREST\Setting;
use FREST\Spec;

require_once(dirname(__FILE__) . '/Request.php');
require_once(dirname(__FILE__) . '/../Spec/Update.php');
require_once(dirname(__FILE__) . '/../Spec/TableUpdate.php');
require_once(dirname(__FILE__) . '/../Result/Update.php');

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
	 * @param Result\Error $error
	 */
	public function setupWithResource($resource, &$error = NULL) {
		parent::setupWithResource($resource, $error);
		if (isset($error)) {
			return;
		}

		if (!isset($this->resourceID)) {
			$error = new Result\Error(Result\Error::MissingResourceID, 400, '');
			return;
		}

		if (!isset($this->parameters) || count($this->parameters) == 0) {
			$error = new Result\Error(Result\Error::NothingToDo, 400, "No update parameters specified");
			return;
		}
		
		$this->updateSpecs = $this->generateUpdateSpecs($this->resource, $error);
		if (isset($error)) {
			return;
		}

		$this->tableUpdateSpecs = $this->generateTableUpdateSpecs($this->resource, $this->updateSpecs, $error);
		if (isset($error)) {
			return;
		}
	}

	/**
	 * @param bool $forceRegen
	 * @return Result\Update|Result\Error
	 */
	public function generateResult($forceRegen = FALSE) {
		$this->frest->startTimingForLabel(Enum\Timing::PROCESSING, 'update');

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

		$this->frest->stopTimingForLabel(Enum\Timing::PROCESSING, 'update');

		$i = 0;
		/** @var Spec\TableUpdate $tableUpdateSpec */
		foreach ($this->tableUpdateSpecs as $tableUpdateSpec) {
			$this->frest->startTimingForLabel(Enum\Timing::PROCESSING, 'update');

			$table = $tableUpdateSpec->getTable();
			$queryParameterSpecs = $tableUpdateSpec->getQueryParameterSpecs();

			$idFieldName = $this->resource->getIDFieldForTable($table);

			$assignmentStringList = $this->generateAssignmentStringList($queryParameterSpecs, $error);
			if (isset($error)) {
				return $error;
			}

			$assignmentString = implode(',', $assignmentStringList);
			
			$this->frest->stopTimingForLabel(Enum\Timing::PROCESSING, 'update');
			$this->frest->startTimingForLabel(Enum\Timing::SQL, 'update');

			$sql = "UPDATE {$table} SET {$assignmentString} WHERE {$idFieldName} = :_id";

			$updateStmt = $pdo->prepare($sql);
			
			$updateStmt->bindValue(
				':_id',
				$this->resourceID,
				Enum\VariableType::pdoTypeFromVariableType($idFieldSetting->getVariableType())
			);

			/** @var Spec\QueryParameter $queryParameterSpec */
			foreach ($queryParameterSpecs as $queryParameterSpec) {
				$updateStmt->bindValue(
					$queryParameterSpec->getParameterName(),
					$queryParameterSpec->getValue(),
					Enum\VariableType::pdoTypeFromVariableType($queryParameterSpec->getVariableType())
				);
			}

			if (!$updateStmt->execute()) {
				if ($isPerformingTransaction) {
					$pdo->rollBack();
				}

				$error = new Result\Error(Result\Error::SQLError, 500, 'Error updating database. '.implode(' ', $updateStmt->errorInfo()));
				return $error;
			}

			$this->frest->stopTimingForLabel(Enum\Timing::SQL, 'update');

			$i++;
		}

		$this->frest->startTimingForLabel(Enum\Timing::SQL, 'update');

		if ($isPerformingTransaction) {
			$pdo->commit();
		}

		$this->frest->stopTimingForLabel(Enum\Timing::SQL, 'update');

		$this->result = new Result\Update();

		return $this->result;
	}



	/**
	 * @param FREST\Resource $resource
	 * @param Result\Error $error
	 * @return array
	 */
	private function generateUpdateSpecs($resource, &$error = NULL) {
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
				$castedValue = Enum\VariableType::castValue($value, $variableType);
				if (!isset($castedValue)) {
					$typeString = Enum\VariableType::getString($variableType);
					$error = new Result\Error(Result\Error::InvalidType, 400, "Expecting '{$alias}' to be of type '{$typeString}' but received '{$value}'");
					return NULL;
				}

				// Condition Func
				$conditionFunction = $updateSetting->getConditionFunction();
				if (isset($conditionFunction)) {
					if (!method_exists($resource, $conditionFunction)) {
						$resourceClassName = get_class($resource);
						$error = new Result\Error(Result\Error::ConditionFunctionMissing, 500, "Function name: '{$conditionFunction}', resource: '{$resourceClassName}'");
						return NULL;
					}

					$isValueValid = $resource->$conditionFunction($castedValue, $error);
					if (isset($error)) {
						return NULL;
					}

					if (!$isValueValid) {
						$error = new Result\Error(Result\Error::InvalidFieldValue, 400, "Field: '{$alias}'");
						return NULL;
					}
				}

				// Filter Func
				$filterFunction = $updateSetting->getFilterFunction();
				if (isset($filterFunction)) {
					if (!method_exists($resource, $filterFunction)) {
						$resourceClassName = get_class($resource);
						$error = new Result\Error(Result\Error::FilterFunctionMissing, 500, "Function name: '{$filterFunction}', resource: '{$resourceClassName}'");
						return NULL;
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
	 * @param Result\Error $error
	 * @return array|NULL
	 */
	protected function generateTableUpdateSpecs($resource, $updateSpecs, &$error = NULL) {
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
				$updateSpecs,
				$error
			);

			if (isset($error)) {
				return NULL;
			}

			$tableUpdateSpecs[] = $tableUpdateSpec;
		}

		return $tableUpdateSpecs;
	}


	/**
	 * @param array $queryParameterSpecs
	 * @param Result\Error $error
	 * @return array
	 */
	private function generateAssignmentStringList($queryParameterSpecs, /** @noinspection PhpUnusedParameterInspection */&$error = NULL) {
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
	 * @param $error
	 * @return bool
	 */
	protected function isValidURLParameter($parameter, $value, &$error) {
		/** @noinspection PhpUndefinedClassInspection */
		$isValid = parent::isValidURLParameter($parameter, $value, $error);
		if (isset($error)) {
			return $isValid;
		}

		if (!$isValid) { // if not already determined to be valid
			$updateSettings = $this->resource->getUpdateSettings();

			if (isset($updateSettings[$parameter])) {
				/** @var Setting\Update $updateSetting */
				$updateSetting = $updateSettings[$parameter];

				$fieldSetting = $this->resource->getFieldSettingForAlias($updateSetting->getAlias());
				if (!isset($fieldSetting)) {
					$resourceName = get_class($this->resource);
					$error = new Result\Error(Result\Error::Config, 500, "No field setting found for condition '{$parameter}' in resource {$resourceName}");
					return FALSE;
				}

				$isValid = TRUE;
			}
		}

		return $isValid;
	}
}