<?php
/**
 * Updated by Brad Walker on 6/4/13 at 5:11 PM
 */

require_once(dirname(__FILE__).'/FRRequest.php');
require_once(dirname(__FILE__).'/../specs/FRUpdateSpec.php');
require_once(dirname(__FILE__).'/../specs/FRTableUpdateSpec.php');
require_once(dirname(__FILE__).'/../results/FRUpdateResult.php');

class FRUpdateRequest extends FRRequest {

	/** @var array */
	protected $updateSpecs;
	
	/** @var array */
	protected $tableUpdateSpecs;

	public function setupWithResource($resource, &$error = NULL) {
		parent::setupWithResource($resource, $error);
		if (isset($error)) {
			return;
		}

		if (!isset($this->resourceID)) {
			$error = new FRErrorResult(FRErrorResult::MissingResourceID, 400, '');
			return;
		}

		if (!isset($this->parameters) || count($this->parameters) == 0) {
			$error = new FRErrorResult(FRErrorResult::NothingToDo, 400, "No update parameters specified");
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

	public function generateResult($forceRegen = FALSE) {
		$this->frest->startTimingForLabel(FRTiming::PROCESSING, 'update');

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

		/** @var FRFieldSetting $idFieldSetting */
		$this->resource->getIDField($idFieldSetting); // populates

		$this->frest->stopTimingForLabel(FRTiming::PROCESSING, 'update');

		$i = 0;
		/** @var FRTableUpdateSpec $tableUpdateSpec */
		foreach ($this->tableUpdateSpecs as $tableUpdateSpec) {
			$this->frest->startTimingForLabel(FRTiming::PROCESSING, 'update');

			$table = $tableUpdateSpec->getTable();
			$queryParameterSpecs = $tableUpdateSpec->getQueryParameterSpecs();

			$idFieldName = $this->resource->getIDFieldForTable($table);

			$assignmentStringList = $this->generateAssignmentStringList($queryParameterSpecs, $error);
			if (isset($error)) {
				return $error;
			}

			$assignmentString = implode(',', $assignmentStringList);
			
			$this->frest->stopTimingForLabel(FRTiming::PROCESSING, 'update');
			$this->frest->startTimingForLabel(FRTiming::SQL, 'update');

			$sql = "UPDATE {$table} SET {$assignmentString} WHERE {$idFieldName} = :_id";

			$updateStmt = $pdo->prepare($sql);
			
			$updateStmt->bindValue(
				':_id',
				$this->resourceID,
				FRVariableType::pdoTypeFromVariableType($idFieldSetting->getVariableType())
			);

			/** @var FRQueryParameterSpec $queryParameterSpec */
			foreach ($queryParameterSpecs as $queryParameterSpec) {
				$updateStmt->bindValue(
					$queryParameterSpec->getParameterName(),
					$queryParameterSpec->getValue(),
					FRVariableType::pdoTypeFromVariableType($queryParameterSpec->getVariableType())
				);
			}

			if (!$updateStmt->execute()) {
				if ($isPerformingTransaction) {
					$pdo->rollBack();
				}

				$error = new FRErrorResult(FRErrorResult::SQLError, 500, 'Error updating database. '.implode(' ', $updateStmt->errorInfo()));
				return $error;
			}

			$this->frest->stopTimingForLabel(FRTiming::SQL, 'update');

			$i++;
		}

		$this->frest->startTimingForLabel(FRTiming::SQL, 'update');

		if ($isPerformingTransaction) {
			$pdo->commit();
		}

		$this->frest->stopTimingForLabel(FRTiming::SQL, 'update');

		$this->result = new FRUpdateResult();

		return $this->result;
	}



	/**
	 * @param FRResource $resource
	 * @param FRErrorResult $error
	 * @return array
	 */
	private function generateUpdateSpecs($resource, &$error = NULL) {
		$updateSpecs = array();

		$updateSettings = $resource->getUpdateSettings();
		
		/** @var FRUpdateSetting $updateSetting */
		foreach ($updateSettings as $updateSetting) {
			$alias = $updateSetting->getAlias();

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
				$conditionFunction = $updateSetting->getConditionFunction();
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
				$filterFunction = $updateSetting->getFilterFunction();
				if (isset($filterFunction)) {
					if (!method_exists($resource, $filterFunction)) {
						$resourceClassName = get_class($resource);
						$error = new FRErrorResult(FRErrorResult::FilterFunctionMissing, 500, "Function name: '{$filterFunction}', resource: '{$resourceClassName}'");
						return NULL;
					}

					$castedValue = $resource->$filterFunction($castedValue);
				}

				$updateSpec = new FRUpdateSpec(
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
	 * @param FRResource $resource
	 * @param array $updateSpecs
	 * @param FRErrorResult $error
	 * @return array|NULL
	 */
	protected function generateTableUpdateSpecs($resource, $updateSpecs, &$error = NULL) {
		$tableUpdateSpecs = array();

		$tablesAndTheirUpdateSpecs = array();

		/** @var FRUpdateSpec $updateSpec */
		foreach ($updateSpecs as $updateSpec) {
			$alias = $updateSpec->getAlias();
			$field = $resource->getFieldForAlias($alias);
			$table = $resource->getTableForField($field);

			$tablesAndTheirUpdateSpecs[$table][] = $updateSpec;
		}

		foreach ($tablesAndTheirUpdateSpecs as $table=>$updateSpecs) {
			$tableUpdateSpec = new FRTableUpdateSpec(
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
	 * @param FRErrorResult $error
	 * @return array
	 */
	private function generateAssignmentStringList($queryParameterSpecs, &$error = NULL) {
		$assignmentStringList = array();
		
		/** @var FRQueryParameterSpec $queryParameterSpec */
		foreach ($queryParameterSpecs as $queryParameterSpec) {
			$field = $queryParameterSpec->getField();
			$parameterName = $queryParameterSpec->getParameterName();
			
			$assignmentString = "{$field}={$parameterName}";

			$assignmentStringList[] = $assignmentString;
		}

		return $assignmentStringList;
	}
	
	
	/**
	 * @param FRErrorResult $error
	 *
	 * @return bool
	 */
	protected function checkForInvalidURLParameters(&$error = NULL) {
		$updateSettings = $this->resource->getUpdateSettings();

		/** @var FRFieldSetting $idFieldSetting */
		$idFieldName = $this->resource->getIDField();
		$idAlias = $this->resource->getAliasForField($idFieldName);
		
		foreach($this->parameters as $parameter=>$value) {
			if (is_array($value)) {
				$error = new FRErrorResult(FRErrorResult::InvalidUsage, 400, "Parameter values (specifically '{$parameter}') are not allowed to be arrays");
				return FALSE;
			}

			$isValidAlias = $parameter != $idAlias && isset($updateSettings[$parameter]);
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