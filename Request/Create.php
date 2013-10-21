<?php
/**
 * Created by Brad Walker on 6/4/13 at 5:11 PM
*/

namespace FREST\Request;

use FREST;
use FREST\Enum;
use FREST\Result;
use FREST\Setting;
use FREST\Spec;

require_once(dirname(__FILE__) . '/Request.php');
require_once(dirname(__FILE__) . '/../Spec/Create.php');
require_once(dirname(__FILE__) . '/../Spec/TableCreate.php');
require_once(dirname(__FILE__) . '/../Result/Create.php');

/**
 * Class Create
 * @package FREST\Request
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
	 * @param null $error
	 */
	public function setupWithResource($resource, &$error = NULL) {
		parent::setupWithResource($resource, $error);
		if (isset($error)) {
			return;
		}

		if (isset($this->resourceID)) {
			$error = new Result\Error(Result\Error::PresentResourceID, 400, '');
			return;
		}
				
		$this->createSpecs = $this->generateCreateSpecs($this->resource, $error);
		if (isset($error)) {
			return;
		}
		
		$this->tableCreateSpecs = $this->generateTableCreateSpecs($this->resource, $this->createSpecs, $error);
		if (isset($error)) {
			return;
		}
	}

	/**
	 * @param bool $forceRegen
	 * @return Result\Create|Result\Error
	 */
	public function generateResult($forceRegen = FALSE) {
		$this->frest->startTimingForLabel(Enum\Timing::PROCESSING, 'create');

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

		$this->frest->stopTimingForLabel(Enum\Timing::PROCESSING, 'create');

		$i = 0;
		/** @var \FREST\Spec\TableCreate $tableCreateSpec */
		foreach ($this->tableCreateSpecs as $tableCreateSpec) {
			$this->frest->startTimingForLabel(Enum\Timing::PROCESSING, 'create');

			$table = $tableCreateSpec->getTable();
			$queryParameterSpecs = $tableCreateSpec->getQueryParameterSpecs();

			if ($i > 0) {
				if (!isset($createdResourceID)) {
					$error = new Result\Error(Result\Error::SQLError, 500, 'No ID generated or set for the created resource');
					return $error;
				}

				// TODO: potential multiple table ID problems
				$alias = 'id';
				$idFieldName = $this->resource->getIDFieldForTable($table);
				
				$idQueryParameterSpec = new Spec\QueryParameter(
					$idFieldName,
					':'.$alias,
					$createdResourceID,
					Enum\VariableType::INT
				);

				// put id spec at beginning of spec list
				$queryParameterSpecs = array($alias => $idQueryParameterSpec) + $tableCreateSpec;
			}
			
			$fieldStringList = $this->generateFieldStringList($queryParameterSpecs);
			
			$parameterStringList = $this->generateParameterStringList($queryParameterSpecs);
			
			$fieldsString = implode(',', $fieldStringList);
			$parametersString = implode(',', $parameterStringList);

			$this->frest->stopTimingForLabel(Enum\Timing::PROCESSING, 'create');
			$this->frest->startTimingForLabel(Enum\Timing::SQL, 'create');

			$sql = "INSERT INTO {$table} ($fieldsString) VALUES ({$parametersString})";

			$createStmt = $pdo->prepare($sql);

			/** @var Spec\QueryParameter $queryParameterSpec */
			foreach ($queryParameterSpecs as $queryParameterSpec) {
				$createStmt->bindValue(
					$queryParameterSpec->getParameterName(),
					$queryParameterSpec->getValue(),
					Enum\VariableType::pdoTypeFromVariableType($queryParameterSpec->getVariableType())
				);
			}

			if (!$createStmt->execute()) {
				if ($isPerformingTransaction) {
					$pdo->rollBack();
				}

				$error = new Result\Error(Result\Error::SQLError, 500, 'Error inserting into database. '.implode(' ', $createStmt->errorInfo()));
				return $error;
			}

			if ($i == 0) {
				$createdResourceID = isset($this->resourceID) ? $this->resourceID : $pdo->lastInsertID();
			}

			$this->frest->stopTimingForLabel(Enum\Timing::SQL, 'create');

			$i++;
		}

		$this->frest->startTimingForLabel(Enum\Timing::SQL, 'create');
		
		if ($isPerformingTransaction) {
			$pdo->commit();
		}
		
		$this->frest->stopTimingForLabel(Enum\Timing::SQL, 'create');
		$this->frest->startTimingForLabel(Enum\Timing::POST_PROCESSING, 'create');

		if (!isset($createdResourceID)) {
			$error = new Result\Error(Result\Error::SQLError, 500, 'No ID generated or set for the created resource');
			return $error;
		}
		
		$this->result = new Result\Create($createdResourceID);

		$this->frest->stopTimingForLabel(Enum\Timing::POST_PROCESSING, 'create');

		return $this->result;
	}


	/**
	 * @param FREST\Resource $resource
	 * @param Result\Error $error
	 * @return array
	 */
	private function generateCreateSpecs($resource, &$error = NULL) {
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
				$castedValue = Enum\VariableType::castValue($value, $variableType);
				if (!isset($castedValue)) {
					$typeString = Enum\VariableType::getString($variableType);
					$error = new Result\Error(Result\Error::InvalidType, 400, "Expecting '{$alias}' to be of type '{$typeString}' but received '{$value}'");
					return NULL;
				}
				
				// Condition Func
				$conditionFunction = $createSetting->getConditionFunction();
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
				$filterFunction = $createSetting->getFilterFunction();
				if (isset($filterFunction)) {
					if (!method_exists($resource, $filterFunction)) {
						$resourceClassName = get_class($resource);
						$error = new Result\Error(Result\Error::FilterFunctionMissing, 500, "Function name: '{$filterFunction}', resource: '{$resourceClassName}'");
						return NULL;
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
				$error = new Result\Error(Result\Error::MissingRequiredParams, 400, "Missing parameters: {$missingParametersString}");
				return NULL;
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
	 * @param Result\Error $error
	 * @return array|NULL
	 */
	protected function generateTableCreateSpecs($resource, $createSpecs, &$error = NULL) {
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
			
			if (isset($error)) {
				return NULL;
			}
			
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
			$createSettings = $this->resource->getCreateSettings();

			if (isset($createSettings[$parameter])) {
				/** @var Setting\Create $createSetting */
				$createSetting = $createSettings[$parameter];

				$fieldSetting = $this->resource->getFieldSettingForAlias($createSetting->getAlias());
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