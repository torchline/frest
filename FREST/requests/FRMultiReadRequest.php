<?php
/**
 * Created by Brad Walker on 6/6/13 at 1:40 PM
*/

require_once(dirname(__FILE__).'/FRReadRequest.php');
require_once(dirname(__FILE__).'/../results/FRMultiReadResult.php');
require_once(dirname(__FILE__).'/../specs/FRConditionSpec.php');
require_once(dirname(__FILE__).'/../specs/FROrderSpec.php');
require_once(dirname(__FILE__) . '/../specs/FRQueryParameterSpec.php');
require_once(dirname(__FILE__) . '/../functions/FRConditionFunction.php');

class FRMultiReadRequest extends FRReadRequest {

	/** @var array */
	protected $queryParameterSpecs;
	
	/** @var array */
	protected $conditionSpecs;
	
	/** @var array */
	protected $orderSpecs;
	
	
	/**
	 * @param FREST $frest
	 * @param array $parameters
	 * @param string $resourceFunctionName
	 */
	public function __construct($frest, $parameters, $resourceFunctionName = NULL, $parentAlias = NULL) {
		$this->miscParameters['limit'] = TRUE;
		$this->miscParameters['offset'] = TRUE;
		$this->miscParameters['orderBy'] = TRUE;
		
		parent::__construct($frest, NULL, $parameters, $resourceFunctionName, $parentAlias);
	}
	
	
	public function setupWithResource($resource, &$error = NULL) {
		parent::setupWithResource($resource, $error);
		if (isset($error)) {
			return;
		}

		// Order Bys
		$this->orderSpecs = $this->generateOrderSpecs($error);
		if (isset($error)) {
			return;
		}

		// Condition
		$this->conditionSpecs = $this->generateConditionSpecs($error);
		if (isset($error)) {
			return;
		}
		
		// Query Parameters
		$this->queryParameterSpecs = NULL; // built in generateResult along with conditionString
	}
	
	public function generateResult($forceRegen = FALSE) {
		$this->frest->startTimingForLabel(FRTiming::PROCESSING, 'multiread');

		$otherResult = parent::generateResult($forceRegen);
		if (isset($otherResult)) {
			return $otherResult;
		}

		// Fields String
		$fieldString = $this->generateFieldString($this->fieldSpecs, $error);
		if (isset($error)) {
			return $error;
		}

		// Tables String
		$tablesToReadString = $this->generateTableString($this->tableSpecs, $error);
		if (isset($error)) {
			return $error;
		}
		
		// Conditions String + Query Parameter Specs
		$conditionString = $this->processConditionSpecs($this->conditionSpecs, $this->joinSpecs, $this->queryParameterSpecs, $error);
		if (isset($error)) {
			return $error;
		}
		
		// Offset
		$offset = $this->generateOffset($this->queryParameterSpecs, $error);
		if (isset($error)) {
			return $error;
		}

		// Offset
		$limit = $this->generateLimit($this->resource, $this->queryParameterSpecs, $error);
		if (isset($error)) {
			return $error;
		}

		// Order By String
		$orderByString = $this->generateOrderString($this->orderSpecs, $error);
		if (isset($error)) {
			return $error;
		}
		
		// Join String
		$joinsString = $this->generateJoinString($this->resource, $this->joinSpecs, $error);
		if (isset($error)) {
			return $error;
		}

		$this->frest->stopTimingForLabel(FRTiming::PROCESSING, 'multiread');
		$this->frest->startTimingForLabel(FRTiming::SQL, 'multiread');

		$pdo = $this->frest->getConfig()->getPDO();

		// SQL
		$sqlParts = array();
		$countSQLParts = array();
		$sqlParts[] = "SELECT {$fieldString} FROM {$tablesToReadString}";
		$countSQLParts[] = "SELECT COUNT(0) AS Count FROM {$tablesToReadString}";
		
		if (strlen($joinsString)) {
			$sqlParts[] = $joinsString;
			$countSQLParts[] = $joinsString;
		}
		if (strlen($conditionString)) {
			$sqlParts[] = $conditionString;
			$countSQLParts[] = $conditionString;
		}
		if (strlen($orderByString)) {
			$sqlParts[] = $orderByString;
		}
		$sqlParts[] = "LIMIT :_offset, :_limit";
		
		$sql = implode(' ', $sqlParts);
		$countSQL = implode(' ', $countSQLParts);

		$resultsStmt = $pdo->prepare($sql);
		$countStmt = $pdo->prepare($countSQL);

		/** @var FRQueryParameterSpec $queryParameterSpec */
		foreach ($this->queryParameterSpecs as $alias=>$queryParameterSpec) {
			$pdoParamType = FRVariableType::pdoTypeFromVariableType($queryParameterSpec->getVariableType());

			if ($alias !== '_limit' && $alias !== '_offset') {
				$countStmt->bindValue(
					$queryParameterSpec->getParameterName(),
					$queryParameterSpec->getValue(),
					$pdoParamType
				);
			}
			
			$resultsStmt->bindValue(
				$queryParameterSpec->getParameterName(),
				$queryParameterSpec->getValue(),
				$pdoParamType
			);
		}

		if (!$resultsStmt->execute()) {
			$error = new FRErrorResult(FRErrorResult::SQLError, 500, 'Error querying database for results. '.implode(' ', $resultsStmt->errorInfo()));
			return $error;
		}

		$objects = $resultsStmt->fetchAll(PDO::FETCH_OBJ);

		if (!$countStmt->execute()) {
			$error = new FRErrorResult(FRErrorResult::SQLError, 500, 'Error querying database for count. '.implode(' ', $countStmt->errorInfo()));
			return $error;
		}

		$countResult = $countStmt->fetchAll(PDO::FETCH_ASSOC);
		$count = intval($countResult[0]['Count']);

		$this->frest->stopTimingForLabel(FRTiming::SQL, 'multiread');

		$this->parseObjects($this->resource, $objects, $this->readSettings, NULL, $error);
		if (isset($error)) {
			return $error;
		}

		$this->result = new FRMultiReadResult($objects, $limit, $offset, $count);

		return $this->result;
	}
	
	
	/** 
	 * @param FRErrorResult $error
	 * @return array
	 */
	private function generateConditionSpecs(&$error = NULL) {
		$conditionSpecs = array();
		
		$conditionSettings = $this->resource->getConditionSettings();

		/** @var FRConditionSetting $conditionSetting */
		foreach ($conditionSettings as $conditionSetting) {
			$alias = $conditionSetting->getAlias();

			if (isset($this->parameters[$alias])) {
				$fieldSetting = $this->resource->getFieldSettingForAlias($alias);

				$value = $this->parameters[$alias];
				$field = $fieldSetting->getField();
				$variableType = $fieldSetting->getVariableType();

				$conditionSpec = new FRConditionSpec(
					$alias,
					$field, 
					$value, 
					$variableType
				);

				$conditionSpecs[$alias] = $conditionSpec;
			}
		}
		
		if (count($conditionSpecs) > 0) {
			return $conditionSpecs;
		}

		return NULL;
	}


	/**
	 * @param array $conditionSpecs
	 * @param array $joinSpecs
	 * @param array $queryParameterSpecs
	 * @param FRError null $error
	 * @return string
	 */
	private function processConditionSpecs($conditionSpecs, $joinSpecs, &$queryParameterSpecs, &$error = NULL) {
		$conditionString = '';
		$queryArrayConditionCount = 0;
		
		if (isset($conditionSpecs)) {
			$conditionString = 'WHERE ';
			$conditionCount = 0;

			$whiteListedFunctions = array(
				FRConditionFunction::GreaterThanFunction(),
				FRConditionFunction::GreaterThanEqualFunction(), 
				FRConditionFunction::LessThanFunction(),
				FRConditionFunction::LessThanEqualFunction(),
				FRConditionFunction::InFunction(),
				FRConditionFunction::LikeFunction()
			);
			
			/** @var FRConditionSpec $conditionSpec */
			foreach ($conditionSpecs as $alias=>$conditionSpec) {
				$functionSQLOperator = "=";
				$queryParameterNames = NULL;
				$queryOperator = '=';
				
				$functionUsed = $this->checkForFunctions(
					$conditionSpec->getValue(), 
					$conditionSpec->getVariableType(),
					$whiteListedFunctions, 
					$parsedValue, 
					$parsedValueVariableType,
					$error
				);
				if (isset($error)) {
					return NULL;
				}

				if (isset($functionUsed)) {
					$queryOperator = $functionUsed->getSqlOperator();
					
					switch ($parsedValueVariableType) {
						case FRVariableType::ARRAY_BOOL:
						case FRVariableType::ARRAY_INT:
						case FRVariableType::ARRAY_FLOAT:
						case FRVariableType::ARRAY_STRING:
							// sort for sql caching purposes (maintains ascending order in case of identical queries)
							asort($parsedValue);

							$parameterNames = array();

							foreach ($parsedValue as $i=>$value) {
								$field = ":in{$queryArrayConditionCount}_{$i}";
								$parameterNames[] = $field;

								$queryParameterSpec = new FRQueryParameterSpec(
									$conditionSpec->getField(),
									$field,
									$value,
									FRVariableType::arrayElementVariableType($parsedValueVariableType)
								);


								$queryParameterSpecs[] = $queryParameterSpec;
							}

							$queryParameterName = '('.implode(',', $parameterNames).')';

							$queryArrayConditionCount++;
							break;
						
						default:
							$queryParameterName = ":{$alias}";

							$queryParameterSpec = new FRQueryParameterSpec(
								$conditionSpec->getField(),
								$queryParameterName,
								$parsedValue,
								$parsedValueVariableType
							);

							$queryParameterSpecs[$alias] = $queryParameterSpec;
							break;
					}
				}
				else {
					$variableType = $conditionSpec->getVariableType();
					if (!isset($variableType)) {
						$variableType = FRVariableType::STRING;
					}

					switch ($variableType) {
						case FRVariableType::ARRAY_BOOL:
						case FRVariableType::ARRAY_INT:
						case FRVariableType::ARRAY_FLOAT:
						case FRVariableType::ARRAY_STRING:
							$variableTypeString = FRVariableType::getString($variableType);
							$error = new FRErrorResult(FRErrorResult::Config, 500, "Invalid type set for condition '{$alias}': '{$variableTypeString}'");
							return NULL;
							break;
							
						default:
							break;
					}

					$castedValue = FRVariableType::castValue($conditionSpec->getValue(), $variableType);
					if (!isset($castedValue)) {
						$variableTypeString = FRVariableType::getString($variableType);
						$error = new FRErrorResult(FRErrorResult::InvalidType, 400, "Expecting field '{$alias}' to be of type '{$variableTypeString}' but received '{$conditionSpec->getValue()}'");
						return NULL;
					}

					$queryParameterName = ":{$alias}";
					
					$queryParameterSpec = new FRQueryParameterSpec(
						$conditionSpec->getField(),
						$queryParameterName,
						$castedValue,
						$variableType
					);

					$queryParameterSpecs[$alias] = $queryParameterSpec;
				}

				$table = $this->resource->getTableForField($conditionSpec->getField());
				$tableAbbreviation = $this->getTableAbbreviation($table);
				$queryField = $conditionSpec->getField();

				if ($conditionCount > 0) {
					$conditionString .= ' AND ';
				}

				$conditionString .= "{$tableAbbreviation}.{$queryField} {$queryOperator} {$queryParameterName}";

				$conditionCount++;
			}
		}

		return $conditionString;
	}

	/**
	 * @param array $queryParameterSpecs
	 * @param FRErrorResult $error
	 * @return int
	 */
	private function generateOffset(&$queryParameterSpecs, &$error = NULL) {
		$offset = isset($this->parameters['offset']) ? $this->parameters['offset'] : 0;
		
		$castedOffset = FRVariableType::castValue($offset, FRVariableType::INT);
		if (!isset($castedOffset)) {
			$typeString = FRVariableType::getString(FRVariableType::INT);
			$error = new FRErrorResult(FRErrorResult::InvalidType, 400, "Expecting offset to be of type '{$typeString}' but received '{$offset}'.");
			return 0;
		}
		
		$offsetQueryParameterSpec = new FRQueryParameterSpec(
			'offset',
			':_offset',
			$castedOffset,
			FRVariableType::INT
		);
		
		$queryParameterSpecs['_offset'] = $offsetQueryParameterSpec;
		
		return $castedOffset;
	}


	/**
	 * @param FRResource $resource
	 * @param array $queryParameterSpecs
	 * @param FRErrorResult $error
	 * @return int
	 */
	private function generateLimit($resource, &$queryParameterSpecs, &$error = NULL) {
		$limit = isset($this->parameters['limit']) ? $this->parameters['limit'] : $resource->getDefaultLimit();

		$castedLimit = FRVariableType::castValue($limit, FRVariableType::INT);
		if (!isset($castedLimit)) {
			$typeString = FRVariableType::getString(FRVariableType::INT);
			$error = new FRErrorResult(FRErrorResult::InvalidType, 400, "Expecting offset to be of type '{$typeString}' but received '{$limit}'.");
			return 0;
		}
		
		$maxLimit = $resource->getMaxLimit();
		if ($castedLimit > $maxLimit) {
			$error = new FRErrorResult(FRErrorResult::InvalidValue, 400, "The limit for this resource must not exceed {$maxLimit}. A limit of {$castedLimit} was supplied.");
			return 0;
		}
		
		$limitQueryParameter = new FRQueryParameterSpec(
			'limit',
			':_limit',
			$castedLimit,
			FRVariableType::INT
		);

		$queryParameterSpecs['_limit'] = $limitQueryParameter;

		return $castedLimit;
	}


	/**
	 * @param FRErrorResult $error
	 * @return array
	 */
	private function generateOrderSpecs(&$error = NULL) {
		$orderSpecs = array();
		
		if (isset($this->parameters['orderBy'])) {
			$orderByParameterString = $this->parameters['orderBy'];
			
			if (strlen($orderByParameterString) == 0) {
				$error = new FRErrorResult(FRErrorResult::InvalidValue, 400, "An empty value was supplied to 'orderBy'");
				return NULL;
			}

			$orderByParameters = explode(',', $orderByParameterString);

			$orderSettings = $this->resource->getOrderSettings();
			
			foreach ($orderByParameters as $orderByParam) {
				$pieces = explode(':', trim($orderByParam));
				$alias = trim($pieces[0]);
				
				$fieldSetting = $this->resource->getFieldSettingForAlias($alias);
				if (!isset($fieldSetting)) {
					$error = new FRErrorResult(FRErrorResult::InvalidValue, 400, "The resource does not have a field named '{$alias}'");
					return NULL;
				}
				
				if (!isset($orderSettings[$alias])) {
					$error = new FRErrorResult(FRErrorResult::InvalidValue, 400, "The resource does not allow ordering with '{$alias}'");
					return NULL;
				}
				
				/** @var FROrderSetting $orderSetting */
				$orderSetting = $orderSettings[$alias];
				
				if (count($pieces) > 1) {
					$userDirection = trim($pieces[1]);
					
					if (strcasecmp('ASC', $userDirection) === 0) {
						$direction = 'ASC';
					}
					else if (strcasecmp('DESC', $userDirection) === 0) {
						$direction = 'DESC';
					}
					else {
						$error = new FRErrorResult(FRErrorResult::InvalidValue, 400, "Order direction '{$userDirection}' is not valid");
						return NULL;
					}
				}
				else {
					if (!$orderSetting->getAscendingEnabled() && !$orderSetting->getDescendingEnabled()) {
						$error = new FRErrorResult(FRErrorResult::InvalidValue, 400, "The resource does not allow ordering with '{$alias}'");
						return NULL;
					}
					
					$ascendingEnabled = $orderSetting->getAscendingEnabled();
					
					$direction = $ascendingEnabled ? 'ASC' : 'DESC';
				}
				
				$field = $this->resource->getFieldForAlias($alias);
				$table = $this->resource->getTableForField($field);
				
				$orderSpec = new FROrderSpec(
					$alias,
					$field,
					$table,
					$direction
				);
				
				$orderSpecs[] = $orderSpec;
			}
		}
		
		if (count($orderSpecs) > 0) {
			return $orderSpecs;
		}
		
		return NULL;
	}


	/**
	 * @param array $orderSpecs
	 * @param FRErrorResult $error
	 * @return string
	 */
	private function generateOrderString($orderSpecs, &$error = NULL) {
		$orderStrings = array();

		if (isset($orderSpecs)) {
			/** @var FROrderSpec $orderSpec */
			foreach ($orderSpecs as $orderSpec) {
				$field = $orderSpec->getField();
				$table = $orderSpec->getTable();
				$tableAbbrv = $this->getTableAbbreviation($table);
				$direction = $orderSpec->getDirection();

				$orderStrings[] = "{$tableAbbrv}.{$field} {$direction}";
			}
		}
		
		if (count($orderStrings) > 0) {
			$orderString = 'ORDER BY '.implode(', ', $orderStrings);
			return $orderString;
		}
		else {
			return NULL;
		}
	}
	

	/**
	 * @param mixed $valueToCheck
	 * @param int $valueVariableType
	 * @param array $functions
	 * @param mixed $parsedValue
	 * @param int $parsedValueVariableType
	 * @param FRErrorResult $error
	 * 
	 * @return FRConditionFunction
	 */
	private function checkForFunctions($valueToCheck, $valueVariableType, $functions, &$parsedValue, &$parsedValueVariableType, &$error = NULL) {
		$functionUsed = NULL;
		
		/** @var FRConditionFunction $function */
		foreach ($functions as $function) {
			$functionName = $function->getName();
			$functionParameters = $function->getParameters();
			$functionReplacements = $function->getReplacements();

			/** @var FRFunctionParameter $firstParameter */
			$firstParameter = $functionParameters[0];

			$beginsWithFunctionOpen = substr_compare("{$functionName}(", $valueToCheck, 0, strlen($functionName) + 1, TRUE) === 0;
			$endsWithFunctionClose = substr($valueToCheck, -1) === ')';

			if ($beginsWithFunctionOpen && $endsWithFunctionClose) {
				$functionUsed = $function;

				$innerValue = trim(substr($valueToCheck, strlen($functionName) + 1, -1));

				if (strlen($innerValue) == 0) {
					$error = new FRErrorResult(FRErrorResult::InvalidValue, 400, "Empty value specified in function '{$functionName}'");
					return NULL;
				}

				if (isset($functionReplacements)) {
					foreach ($functionReplacements as $old => $new) {
						$innerValue = str_replace($old, $new, $innerValue);
					}
				}

				$valuesToConcatenate = NULL;

				$innerValueVariableType = FRVariableType::stricterVariableType($firstParameter->getVariableType(), $valueVariableType);

				$castedValue = FRVariableType::castValue($innerValue, $innerValueVariableType);				
				if (!isset($castedValue)) {
					$variableTypeString = FRVariableType::getString($innerValueVariableType);
					$error = new FRErrorResult(FRErrorResult::InvalidType, 400, "Expecting value for function '{$functionName}' to be of type '{$variableTypeString}' but received '{$innerValue}'");
					return NULL;
				}
				
				$parsedValue = $castedValue;
				$parsedValueVariableType = $innerValueVariableType;

				break; // found use of function already, don't keep checking
			}
		}
		
		return $functionUsed;
	}
	

	/**
	 * @param FRErrorResult $error
	 *
	 * @return bool
	 */
	protected function checkForInvalidURLParameters(&$error = NULL) {		
		$readSettings = $this->resource->getReadSettings();

		foreach($this->parameters as $parameter=>$value) {
			if (is_array($value)) {
				$error = new FRErrorResult(FRErrorResult::InvalidUsage, 400, "Parameter values (specifically '{$parameter}') are not allowed to be arrays");
				return FALSE;
			}

			$isValidAlias = isset($readSettings[$parameter]);
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