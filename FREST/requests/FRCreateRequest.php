<?php
/**
 * Created by Brad Walker on 6/4/13 at 5:11 PM
*/

require_once(dirname(__FILE__).'/FRRequest.php');
require_once(dirname(__FILE__).'/../specs/FRCreateSpec.php');
require_once(dirname(__FILE__).'/../specs/FRTableCreateSpec.php');
require_once(dirname(__FILE__).'/../results/FRCreateResult.php');

class FRCreateRequest extends FRRequest {
	
	/** @var array */
	protected $createSpecs;
	
	/** @var array */
	protected $tableCreateSpecs;
	
	/** @var array */
	protected $queryParameterSpecs;
	
	
	public function setupWithResource($resource, &$error = NULL) {
		parent::setupWithResource($resource, $error);
		if (isset($error)) {
			return;
		}

		if (isset($this->resourceID)) {
			$error = new FRErrorResult(FRErrorResult::PresentResourceID, 400, '');
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
	
	
	public function generateResult($forceRegen = FALSE) {
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
		
		$i = 0;
		/** @var FRTableCreateSpec $tableCreateSpec */
		foreach ($this->tableCreateSpecs as $tableCreateSpec) {
			$table = $tableCreateSpec->getTable();
			$queryParameterSpecs = $tableCreateSpec->getQueryParameterSpecs();

			$idFieldName = $this->resource->getIDFieldForTable($table);
			//$idAlias = $this->resource->getAliasForField($idFieldName);
			
			if ($i > 0) {
				if (!isset($createdResourceID)) {
					$error = new FRErrorResult(FRErrorResult::SQLError, 500, 'No ID generated or set for the created resource');
					return $error;
				}

				// TODO: potential multiple table ID problems
				$alias = 'id';
				$idFieldName = $this->resource->getIDFieldForTable($table);
				
				$idQueryParameterSpec = new FRQueryParameterSpec(
					$idFieldName,
					':'.$alias,
					$createdResourceID,
					FRVariableType::INT
				);

				// put id spec at beginning of spec list
				$queryParameterSpecs = array($alias => $idQueryParameterSpec) + $tableCreateSpec;
			}
			
			$fieldStringList = $this->generateFieldStringList($queryParameterSpecs, $error);
			if ($error) {
				return $error;
			}

			$parameterStringList = $this->generateParameterStringList($queryParameterSpecs, $error);
			if ($error) {
				return $error;
			}

			$fieldsString = implode(',', $fieldStringList);
			$parametersString = implode(',', $parameterStringList);

			$sql = "INSERT INTO {$table} ($fieldsString) VALUES ({$parametersString})";

			$createStmt = $pdo->prepare($sql);

			/** @var FRQueryParameterSpec $queryParameterSpec */
			foreach ($queryParameterSpecs as $queryParameterSpec) {
				$createStmt->bindValue(
					$queryParameterSpec->getParameterName(),
					$queryParameterSpec->getValue(),
					FRVariableType::pdoTypeFromVariableType($queryParameterSpec->getVariableType())
				);
			}

			if (!$createStmt->execute()) {
				if ($isPerformingTransaction) {
					$pdo->rollBack();
				}

				$error = new FRErrorResult(FRErrorResult::SQLError, 500, 'Error inserting into database. '.implode(' ', $createStmt->errorInfo()));
				return $error;
			}

			if ($i == 0) {
				$createdResourceID = isset($this->resourceID) ? $this->resourceID : $pdo->lastInsertID();
			}

			$i++;
		}

		if ($isPerformingTransaction) {
			$pdo->commit();
		}

		if (!isset($createdResourceID)) {
			$error = new FRErrorResult(FRErrorResult::SQLError, 500, 'No ID generated or set for the created resource');
			return $error;
		}
		
		$this->result = new FRCreateResult($createdResourceID);

		return $this->result;
	}


	/**
	 * @param FRResource $resource
	 * @param FRErrorResult $error
	 * @return array
	 */
	private function generateCreateSpecs($resource, &$error = NULL) {
		$createSpecs = array();

		$createSettings = $resource->getCreateSettings();

		if (isset($this->resourceID)) {
			/** @var FRFieldSetting $idFieldSetting */
			$idFieldName = $this->resource->getIDField($idFieldSetting);
			$idAlias = $this->resource->getAliasForField($idFieldName);

			$idCreateSpec = new FRCreateSpec(
				$idAlias,
				$idFieldName,
				$this->resourceID,
				$idFieldSetting->getVariableType()
			);
			
			$createSpecs[$idAlias] = $idCreateSpec;
		}
		
		/** @var FRCreateSetting $createSetting */
		foreach ($createSettings as $createSetting) {
			$alias = $createSetting->getAlias();
			
			if (isset($this->parameters[$alias])) {
				$value = $this->parameters[$alias];

				$field = $resource->getFieldForAlias($alias);
				$fieldSetting = $resource->getFieldSettingForAlias($alias);
				$variableType = $fieldSetting->getVariableType();
				
				// Type checking
				$castedValue = FRVariableType::castValue($value, $variableType);
				if (!isset($castedValue)) {
					$typeString = FRVariableType::getString($variableType);
					$error = new FRErrorResult(FRErrorResult::InvalidType, 400, "Expecting '{$alias}' to be of type '{$typeString}' but received '{$value}'");
					return NULL;
				}
				
				// Condition function
				$conditionFunction = $createSetting->getConditionFunction();
				if (isset($conditionFunction)) {
					if (!method_exists($resource, $conditionFunction)) {
						$resourceClassName = get_class($resource);
						$error = new FRErrorResult(FRErrorResult::ConditionFunctionMissing, 500, "Function name: '{$conditionFunction}', resource: '{$resourceClassName}'");
						return NULL;
					}
					
					$isValueValid = $resource->$conditionFunction($castedValue, $error);
					if (isset($error)) {
						return NULL;
					}
					
					if (!$isValueValid) {
						$error = new FRErrorResult(FRErrorResult::InvalidFieldValue, 400, "Field: '{$alias}'");
						return NULL;
					}
				}

				// Filter function
				$filterFunction = $createSetting->getFilterFunction();
				if (isset($filterFunction)) {
					if (!method_exists($resource, $filterFunction)) {
						$resourceClassName = get_class($resource);
						$error = new FRErrorResult(FRErrorResult::FilterFunctionMissing, 500, "Function name: '{$filterFunction}', resource: '{$resourceClassName}'");
						return NULL;
					}

					$castedValue = $resource->$filterFunction($castedValue);
				}
				
				$createSpec = new FRCreateSpec(
					$alias,
					$field,
					$castedValue,
					$variableType
				);
				
				$createSpecs[$alias] = $createSpec;
			}
			else if ($createSetting->getRequired()) {
				$error = new FRErrorResult(FRErrorResult::MissingRequiredParams, 400, "Missing the '{$alias}' parameter.");
				return NULL;
			}
		}

		if (count($createSpecs) > 0) {
			return $createSpecs;
		}

		return NULL;
	}


	/**
	 * @param FRResource $resource
	 * @param array $createSpecs
	 * @param FRErrorResult $error
	 * @return array|NULL
	 */
	protected function generateTableCreateSpecs($resource, $createSpecs, &$error = NULL) {
		$tableCreateSpecs = array();

		$tablesAndTheirCreateSpecs = array();
		
		/** @var FRCreateSpec $createSpec */
		foreach ($createSpecs as $createSpec) {
			$alias = $createSpec->getAlias();
			$field = $resource->getFieldForAlias($alias);
			$table = $resource->getTableForField($field);

			$tablesAndTheirCreateSpecs[$table][] = $createSpec;
		}

		foreach ($tablesAndTheirCreateSpecs as $table=>$createSpecs) {
			$tableCreateSpec = new FRTableCreateSpec(
				$table,
				$createSpecs,
				$error
			);
			
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
	 * @param FRErrorResult $error
	 * @return array
	 */
	private function generateFieldStringList($queryParameterSpecs, &$error = NULL) {
		$fieldStringList = array();

		/** @var FRQueryParameterSpec $queryParameterSpec */
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
	 * @param FRErrorResult $error
	 * @return array
	 */
	private function generateParameterStringList($queryParameterSpecs, &$error = NULL) {
		$parameterStringList = array();

		/** @var FRQueryParameterSpec $queryParameterSpec */
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
	 * @param FRErrorResult $error
	 *
	 * @return bool
	 */
	protected function checkForInvalidURLParameters(&$error = NULL) {
		$createSettings = $this->resource->getCreateSettings();

		/** @var FRFieldSetting $idFieldSetting */
		$idFieldName = $this->resource->getIDField();
		$idAlias = $this->resource->getAliasForField($idFieldName);
		
		foreach($this->parameters as $parameter=>$value) {
			if (is_array($value)) {
				$error = new FRErrorResult(FRErrorResult::InvalidUsage, 400, "Parameter values (specifically '{$parameter}') are not allowed to be arrays");
				return FALSE;
			}

			$isValidAlias = $parameter != $idAlias && isset($createSettings[$parameter]);
			$isValidMiscParam = isset($this->miscParameters[$parameter]);

			if ($isValidAlias) {
				if ($isValidMiscParam) {
					$error = new FRErrorResult(FRErrorResult::Config, 500, "The alias '{$parameter}' is reserved for internal use and must not be used");
					return FALSE;
				}
			}
			else if (!$isValidMiscParam) {
				$error = new FRErrorResult(FRErrorResult::InvalidField, 400, "Invalid parameter used in query: '{$parameter}'");
				return FALSE;
			}
		}

		return TRUE;
	}
}