<?php
/**
 * Created by Brad Walker on 6/4/13 at 5:11 PM
*/

namespace FREST\Request;

use FREST;
use FREST\Type;
use FREST\Result;
use FREST\Setting;
use FREST\Spec;

/**
 * Class Create
 * @package Router\Request
 */
class Create extends Request {
	
	/** @var array */
	protected $createSpecs;
	
	/** @var array */
	protected $tableCreateSpecs;
	
	/** @var array */
	protected $queryParameterSpecs;

	/**
	 * @param FREST\Resource $resource
	 * @throws FREST\Exception
	 */
	public function setupWithResource($resource) {
		parent::setupWithResource($resource);
		
		if (isset($this->resourceID)) {
			throw new FREST\Exception(FREST\Exception::PresentResourceID);
		}
				
		$this->createSpecs = $this->generateCreateSpecs($this->resource);
		$this->tableCreateSpecs = $this->generateTableCreateSpecs($this->resource, $this->createSpecs);
	}

	/**
	 * @param bool $forceRegen
	 * @return Result\Create
	 * @throws FREST\Exception
	 */
	public function generateResult($forceRegen = FALSE) {
		$this->frest->startTimingForLabel(Type\Timing::PROCESSING, 'create');

		$otherResult = parent::generateResult($forceRegen);
		if (isset($otherResult)) {
			return $otherResult;
		}

		$pdo = $this->frest->getConfig()->getPDO();

		$isPerformingTransaction = FALSE;
		
		if (count($this->tableCreateSpecs) > 1) {
			$pdo->beginTransaction();
			$isPerformingTransaction = TRUE;
		}

		$this->frest->stopTimingForLabel(Type\Timing::PROCESSING, 'create');

		$i = 0;
		/** @var \FREST\Spec\TableCreate $tableCreateSpec */
		foreach ($this->tableCreateSpecs as $tableCreateSpec) {
			$this->frest->startTimingForLabel(Type\Timing::PROCESSING, 'create');

			$table = $tableCreateSpec->getTable();
			$queryParameterSpecs = $tableCreateSpec->getQueryParameterSpecs();

			if ($i > 0) {
				if (!isset($createdResourceID)) {
					throw new FREST\Exception(FREST\Exception::SQLError, 'No ID generated or set for the created resource');
				}

				// TODO: potential multiple table ID problems
				$alias = 'id';
				$idFieldName = $this->resource->getIDFieldForTable($table);
				
				$idQueryParameterSpec = new Spec\QueryParameter(
					$idFieldName,
					':'.$alias,
					$createdResourceID,
					Type\Variable::INT
				);

				// put id spec at beginning of spec list
				$queryParameterSpecs = array($alias => $idQueryParameterSpec) + $tableCreateSpec;
			}
			
			$fieldStringList = $this->generateFieldStringList($queryParameterSpecs);
			
			$parameterStringList = $this->generateParameterStringList($queryParameterSpecs);
			
			$fieldsString = implode(',', $fieldStringList);
			$parametersString = implode(',', $parameterStringList);

			$this->frest->stopTimingForLabel(Type\Timing::PROCESSING, 'create');
			$this->frest->startTimingForLabel(Type\Timing::SQL, 'create');

			$sql = "INSERT INTO {$table} ($fieldsString) VALUES ({$parametersString})";

			$createStmt = $pdo->prepare($sql);

			/** @var Spec\QueryParameter $queryParameterSpec */
			foreach ($queryParameterSpecs as $queryParameterSpec) {
				$createStmt->bindValue(
					$queryParameterSpec->getParameterName(),
					$queryParameterSpec->getValue(),
					Type\Variable::pdoTypeFromVariableType($queryParameterSpec->getVariableType())
				);
			}

			if (!$createStmt->execute()) {
				if ($isPerformingTransaction) {
					$pdo->rollBack();
				}

				throw new FREST\Exception(FREST\Exception::SQLError, 'Error inserting into database');
			}

			if ($i == 0) {
				$createdResourceID = isset($this->resourceID) ? $this->resourceID : $pdo->lastInsertID();
			}

			$this->frest->stopTimingForLabel(Type\Timing::SQL, 'create');

			$i++;
		}

		$this->frest->startTimingForLabel(Type\Timing::SQL, 'create');
		
		if ($isPerformingTransaction) {
			$pdo->commit();
		}
		
		$this->frest->stopTimingForLabel(Type\Timing::SQL, 'create');
		$this->frest->startTimingForLabel(Type\Timing::POST_PROCESSING, 'create');

		if (!isset($createdResourceID)) {
			throw new FREST\Exception(FREST\Exception::SQLError, 'No ID generated or set for the created resource');
		}
		
		$this->result = new Result\Create($createdResourceID);

		$this->frest->stopTimingForLabel(Type\Timing::POST_PROCESSING, 'create');

		return $this->result;
	}


	/**
	 * @param FREST\Resource $resource
	 * @return array
	 * @throws FREST\Exception
	 */
	private function generateCreateSpecs($resource) {
		$createSpecs = array();

		$createSettings = $resource->getCreateSettings();

		if (isset($this->resourceID)) {
			/** @var Setting\Field $idFieldSetting */
			$idFieldName = $this->resource->getIDField($idFieldSetting);
			$idAlias = $this->resource->getAliasForField($idFieldName);

			$idCreateSpec = new Spec\Create(
				$idAlias,
				$idFieldName,
				$this->resourceID,
				$idFieldSetting->getVariableType()
			);
			
			$createSpecs[$idAlias] = $idCreateSpec;
		}
		
		/** @var Setting\Create $createSetting */
		foreach ($createSettings as $createSetting) {
			$alias = $createSetting->getAlias();
			
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
				$conditionFunction = $createSetting->getConditionFunction();
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
				$filterFunction = $createSetting->getFilterFunction();
				if (isset($filterFunction)) {
					if (!method_exists($resource, $filterFunction)) {
						$resourceClassName = get_class($resource);
						throw new FREST\Exception(FREST\Exception::FilterFunctionMissing, "Function name: '{$filterFunction}', resource: '{$resourceClassName}'");
					}

					$castedValue = $resource->$filterFunction($castedValue);
				}
				
				$createSpec = new Spec\Create(
					$alias,
					$field,
					$castedValue,
					$variableType
				);
				
				$createSpecs[$alias] = $createSpec;
			}
			else if ($createSetting->getRequired()) {
				// get list of all parameters required but not set
				$missingParameters = array();
				/** @var Setting\Create $aCreateSetting */
				foreach ($createSettings as $aCreateSetting) {
					$alias = $aCreateSetting->getAlias();
					
					if (!isset($this->parameters[$alias]) && $aCreateSetting->getRequired()) {
						$missingParameters[] = $alias;
					}
				}
				$missingParametersString = implode(', ', $missingParameters);
				throw new FREST\Exception(FREST\Exception::MissingRequiredParams, "Missing parameters: {$missingParametersString}");
			}
		}

		if (count($createSpecs) > 0) {
			return $createSpecs;
		}

		return NULL;
	}


	/**
	 * @param FREST\Resource $resource
	 * @param array $createSpecs
	 * @return array|NULL
	 */
	protected function generateTableCreateSpecs($resource, $createSpecs) {
		$tableCreateSpecs = array();

		$tablesAndTheirCreateSpecs = array();
		
		/** @var Spec\Create $createSpec */
		foreach ($createSpecs as $createSpec) {
			$alias = $createSpec->getAlias();
			$field = $resource->getFieldForAlias($alias);
			$table = $resource->getTableForField($field);

			$tablesAndTheirCreateSpecs[$table][] = $createSpec;
		}

		foreach ($tablesAndTheirCreateSpecs as $table=>$createSpecs) {
			$tableCreateSpec = new Spec\TableCreate($table, $createSpecs);
			$tableCreateSpecs[] = $tableCreateSpec;
		}

		if (count($tableCreateSpecs) > 0) {
			return $tableCreateSpecs;
		}

		return NULL;
	}


	/**
	 * @param array $queryParameterSpecs
	 * @return array
	 */
	private function generateFieldStringList($queryParameterSpecs) {
		$fieldStringList = array();

		/** @var Spec\QueryParameter $queryParameterSpec */
		foreach ($queryParameterSpecs as $queryParameterSpec) {
			$field = $queryParameterSpec->getField();

			$fieldStringList[] = $field;
		}
		
		if (count($fieldStringList) > 0) {
			return $fieldStringList;
		}
		
		return NULL;
	}


	/**
	 * @param array $queryParameterSpecs
	 * @return array
	 */
	private function generateParameterStringList($queryParameterSpecs) {
		$parameterStringList = array();

		/** @var Spec\QueryParameter $queryParameterSpec */
		foreach ($queryParameterSpecs as $queryParameterSpec) {
			$parameterName = $queryParameterSpec->getParameterName();

			$parameterStringList[] = $parameterName;
		}

		if (count($parameterStringList) > 0) {
			return $parameterStringList;
		}

		return NULL;
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
			$createSettings = $this->resource->getCreateSettings();

			if (isset($createSettings[$parameter])) {
				/** @var Setting\Create $createSetting */
				$createSetting = $createSettings[$parameter];

				$fieldSetting = $this->resource->getFieldSettingForAlias($createSetting->getAlias());
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