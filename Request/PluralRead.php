<?php
/**
 * Created by Brad Walker on 6/6/13 at 1:40 PM
*/

namespace FREST\Request;

use FREST;
use FREST\Enum;
use FREST\Func;
use FREST\Result;
use FREST\Setting;
use FREST\Spec;

require_once(dirname(__FILE__) . '/Read.php');
require_once(dirname(__FILE__) . '/../Result/PluralRead.php');
require_once(dirname(__FILE__) . '/../Spec/Condition.php');
require_once(dirname(__FILE__) . '/../Spec/Order.php');
require_once(dirname(__FILE__) . '/../Spec/QueryParameter.php');
require_once(dirname(__FILE__) . '/../Func/Condition.php');

/**
 * Class PluralRead
 * @package FREST\Request
 */
class PluralRead extends Read {

	/** @var array */
	protected $queryParameterSpecs;
	
	/** @var array */
	protected $conditionSpecs;
	
	/** @var array */
	protected $orderSpecs;


	/**
	 * @param FREST\FREST $frest
	 * @param null $parameters
	 * @param null $resourceFunctionName
	 * @param null $parentAlias
	 */
	public function __construct($frest, $parameters, $resourceFunctionName = NULL, $parentAlias = NULL) {
		$this->miscParameters['limit'] = TRUE;
		$this->miscParameters['offset'] = TRUE;
		$this->miscParameters['orderBy'] = TRUE;
		
		parent::__construct($frest, NULL, $parameters, $resourceFunctionName, $parentAlias);
	}

	/**
	 * @param FREST\Resource $resource
	 * @param null $error
	 */
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

	/**
	 * @param bool $forceRegen
	 * @return Result\PluralRead|Result\Error
	 */
	public function generateResult($forceRegen = FALSE) {
		$this->frest->startTimingForLabel(Enum\Timing::PROCESSING, 'pluralread');

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
		$conditionString = $this->processConditionSpecs($this->conditionSpecs, $this->queryParameterSpecs, $error);
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

		$this->frest->stopTimingForLabel(Enum\Timing::PROCESSING, 'pluralread');
		$this->frest->startTimingForLabel(Enum\Timing::SQL, 'pluralread');

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

		/** @var Spec\QueryParameter $queryParameterSpec */
		foreach ($this->queryParameterSpecs as $alias=>$queryParameterSpec) {
			$pdoParamType = Enum\VariableType::pdoTypeFromVariableType($queryParameterSpec->getVariableType());

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
			$error = new Result\Error(Result\Error::SQLError, 500, 'Error querying database for Result. '.implode(' ', $resultsStmt->errorInfo()));
			return $error;
		}

		$objects = $resultsStmt->fetchAll(\PDO::FETCH_OBJ);

		if (!$countStmt->execute()) {
			$error = new Result\Error(Result\Error::SQLError, 500, 'Error querying database for count. '.implode(' ', $countStmt->errorInfo()));
			return $error;
		}

		$countResult = $countStmt->fetchAll(\PDO::FETCH_ASSOC);
		$count = intval($countResult[0]['Count']);

		$this->frest->stopTimingForLabel(Enum\Timing::SQL, 'pluralread');

		$this->parseObjects($this->resource, $objects, $this->readSettings, NULL, $error);
		if (isset($error)) {
			return $error;
		}

		$this->result = new Result\PluralRead($objects, $limit, $offset, $count);

		return $this->result;
	}
	
	
	/** 
	 * @param Result\Error $error
	 * @return array
	 */
	private function generateConditionSpecs(/** @noinspection PhpUnusedParameterInspection */&$error = NULL) {
		$conditionSpecs = array();
		
		$conditionSettings = $this->resource->getConditionSettings();

		/** @var Setting\Condition $conditionSetting */
		foreach ($conditionSettings as $conditionSetting) {
			$alias = $conditionSetting->getAlias();

			if (isset($this->parameters[$alias])) {
				$fieldSetting = $this->resource->getFieldSettingForAlias($alias);

				$value = $this->parameters[$alias];
				$field = $fieldSetting->getField();
				$variableType = $fieldSetting->getVariableType();

				$conditionSpec = new Spec\Condition(
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
	 * @param array $queryParameterSpecs
	 * @param FRError null $error
	 * @return string
	 */
	private function processConditionSpecs($conditionSpecs, &$queryParameterSpecs, &$error = NULL) {
		$conditionString = '';
		$queryArrayConditionCount = 0;
		
		if (isset($conditionSpecs)) {
			$conditionString = 'WHERE ';
			$conditionCount = 0;

			$whiteListedFunctions = array(
				Func\Condition::GreaterThanFunction(),
				Func\Condition::GreaterThanEqualFunction(),
				Func\Condition::LessThanFunction(),
				Func\Condition::LessThanEqualFunction(),
				Func\Condition::InFunction(),
				Func\Condition::LikeFunction()
			);
			
			/** @var Spec\Condition $conditionSpec */
			foreach ($conditionSpecs as $alias=>$conditionSpec) {
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
						case Enum\VariableType::ARRAY_BOOL:
						case Enum\VariableType::ARRAY_INT:
						case Enum\VariableType::ARRAY_FLOAT:
						case Enum\VariableType::ARRAY_STRING:
							// sort for sql caching purposes (maintains ascending order in case of identical queries)
							asort($parsedValue);

							$parameterNames = array();

							foreach ($parsedValue as $i=>$value) {
								$field = ":in{$queryArrayConditionCount}_{$i}";
								$parameterNames[] = $field;

								$queryParameterSpec = new Spec\QueryParameter(
									$conditionSpec->getField(),
									$field,
									$value,
									Enum\VariableType::arrayElementVariableType($parsedValueVariableType)
								);


								$queryParameterSpecs[] = $queryParameterSpec;
							}

							$queryParameterName = '('.implode(',', $parameterNames).')';

							$queryArrayConditionCount++;
							break;
						
						default:
							$queryParameterName = ":{$alias}";

							$queryParameterSpec = new Spec\QueryParameter(
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
						$variableType = Enum\VariableType::STRING;
					}

					switch ($variableType) {
						case Enum\VariableType::ARRAY_BOOL:
						case Enum\VariableType::ARRAY_INT:
						case Enum\VariableType::ARRAY_FLOAT:
						case Enum\VariableType::ARRAY_STRING:
							$variableTypeString = Enum\VariableType::getString($variableType);
							$error = new Result\Error(Result\Error::Config, 500, "Invalid type set for condition '{$alias}': '{$variableTypeString}'");
							return NULL;
							break;
							
						default:
							break;
					}

					$castedValue = Enum\VariableType::castValue($conditionSpec->getValue(), $variableType);
					if (!isset($castedValue)) {
						$variableTypeString = Enum\VariableType::getString($variableType);
						$error = new Result\Error(Result\Error::InvalidType, 400, "Expecting field '{$alias}' to be of type '{$variableTypeString}' but received '{$conditionSpec->getValue()}'");
						return NULL;
					}

					$queryParameterName = ":{$alias}";
					
					$queryParameterSpec = new Spec\QueryParameter(
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
	 * @param Result\Error $error
	 * @return int
	 */
	private function generateOffset(&$queryParameterSpecs, &$error = NULL) {
		$offset = isset($this->parameters['offset']) ? $this->parameters['offset'] : 0;
		
		$castedOffset = Enum\VariableType::castValue($offset, Enum\VariableType::INT);
		if (!isset($castedOffset)) {
			$typeString = Enum\VariableType::getString(Enum\VariableType::INT);
			$error = new Result\Error(Result\Error::InvalidType, 400, "Expecting offset to be of type '{$typeString}' but received '{$offset}'.");
			return 0;
		}
		
		$offsetQueryParameterSpec = new Spec\QueryParameter(
			'offset',
			':_offset',
			$castedOffset,
			Enum\VariableType::INT
		);
		
		$queryParameterSpecs['_offset'] = $offsetQueryParameterSpec;
		
		return $castedOffset;
	}


	/**
	 * @param FREST\Resource $resource
	 * @param array $queryParameterSpecs
	 * @param Result\Error $error
	 * @return int
	 */
	private function generateLimit($resource, &$queryParameterSpecs, &$error = NULL) {
		$limit = isset($this->parameters['limit']) ? $this->parameters['limit'] : $resource->getDefaultLimit();

		$castedLimit = Enum\VariableType::castValue($limit, Enum\VariableType::INT);
		if (!isset($castedLimit)) {
			$typeString = Enum\VariableType::getString(Enum\VariableType::INT);
			$error = new Result\Error(Result\Error::InvalidType, 400, "Expecting offset to be of type '{$typeString}' but received '{$limit}'.");
			return 0;
		}
		
		$maxLimit = $resource->getMaxLimit();
		if ($castedLimit > $maxLimit) {
			$error = new Result\Error(Result\Error::InvalidValue, 400, "The limit for this resource must not exceed {$maxLimit}. A limit of {$castedLimit} was supplied.");
			return 0;
		}
		
		$limitQueryParameter = new Spec\QueryParameter(
			'limit',
			':_limit',
			$castedLimit,
			Enum\VariableType::INT
		);

		$queryParameterSpecs['_limit'] = $limitQueryParameter;

		return $castedLimit;
	}


	/**
	 * @param Result\Error $error
	 * @return array
	 */
	private function generateOrderSpecs(&$error = NULL) {
		$orderSpecs = array();
		
		if (isset($this->parameters['orderBy'])) {
			$orderByParameterString = $this->parameters['orderBy'];
			
			if (strlen($orderByParameterString) == 0) {
				$error = new Result\Error(Result\Error::InvalidValue, 400, "An empty value was supplied to 'orderBy'");
				return NULL;
			}

			$orderByParameters = explode(',', $orderByParameterString);

			$orderSettings = $this->resource->getOrderSettings();
			
			foreach ($orderByParameters as $orderByParam) {
				$pieces = explode(':', trim($orderByParam));
				$alias = trim($pieces[0]);
				
				$fieldSetting = $this->resource->getFieldSettingForAlias($alias);
				if (!isset($fieldSetting)) {
					$error = new Result\Error(Result\Error::InvalidValue, 400, "The resource does not have a field named '{$alias}'");
					return NULL;
				}
				
				if (!isset($orderSettings[$alias])) {
					$error = new Result\Error(Result\Error::InvalidValue, 400, "The resource does not allow ordering with '{$alias}'");
					return NULL;
				}
				
				/** @var Setting\Order $orderSetting */
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
						$error = new Result\Error(Result\Error::InvalidValue, 400, "Order direction '{$userDirection}' is not valid");
						return NULL;
					}
				}
				else {
					if (!$orderSetting->getAscendingEnabled() && !$orderSetting->getDescendingEnabled()) {
						$error = new Result\Error(Result\Error::InvalidValue, 400, "The resource does not allow ordering with '{$alias}'");
						return NULL;
					}
					
					$ascendingEnabled = $orderSetting->getAscendingEnabled();
					
					$direction = $ascendingEnabled ? 'ASC' : 'DESC';
				}
				
				$field = $this->resource->getFieldForAlias($alias);
				$table = $this->resource->getTableForField($field);
				
				$orderSpec = new Spec\Order(
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
	 * @param Result\Error $error
	 * @return string
	 */
	private function generateOrderString($orderSpecs, /** @noinspection PhpUnusedParameterInspection */&$error = NULL) {
		$orderStrings = array();

		if (isset($orderSpecs)) {
			/** @var Spec\Order $orderSpec */
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
	 * @param Result\Error $error
	 * 
	 * @return Func\Condition
	 */
	private function checkForFunctions($valueToCheck, $valueVariableType, $functions, &$parsedValue, &$parsedValueVariableType, &$error = NULL) {
		$functionUsed = NULL;
		
		/** @var Func\Condition $function */
		foreach ($functions as $function) {
			$functionName = $function->getName();
			$functionParameters = $function->getParameters();
			$functionReplacements = $function->getReplacements();

			/** @var Func\FunctionParam $firstParameter */
			$firstParameter = $functionParameters[0];

			$beginsWithFunctionOpen = substr_compare("{$functionName}(", $valueToCheck, 0, strlen($functionName) + 1, TRUE) === 0;
			$endsWithFunctionClose = substr($valueToCheck, -1) === ')';

			if ($beginsWithFunctionOpen && $endsWithFunctionClose) {
				$functionUsed = $function;

				$innerValue = trim(substr($valueToCheck, strlen($functionName) + 1, -1));

				if (strlen($innerValue) == 0) {
					$error = new Result\Error(Result\Error::InvalidValue, 400, "Empty value specified in Func '{$functionName}'");
					return NULL;
				}

				if (isset($functionReplacements)) {
					foreach ($functionReplacements as $old => $new) {
						$innerValue = str_replace($old, $new, $innerValue);
					}
				}

				$valuesToConcatenate = NULL;

				$innerValueVariableType = Enum\VariableType::stricterVariableType($firstParameter->getVariableType(), $valueVariableType);

				$castedValue = Enum\VariableType::castValue($innerValue, $innerValueVariableType);				
				if (!isset($castedValue)) {
					$variableTypeString = Enum\VariableType::getString($innerValueVariableType);
					$error = new Result\Error(Result\Error::InvalidType, 400, "Expecting value for Func '{$functionName}' to be of type '{$variableTypeString}' but received '{$innerValue}'");
					return NULL;
				}
				
				$parsedValue = $castedValue;
				$parsedValueVariableType = $innerValueVariableType;

				break; // found use of Func already, don't keep checking
			}
		}
		
		return $functionUsed;
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
			$conditionSettings = $this->resource->getConditionSettings();
			
			if (isset($conditionSettings[$parameter])) {
				/** @var Setting\Condition $conditionSetting */
				$conditionSetting = $conditionSettings[$parameter];
				
				$fieldSetting = $this->resource->getFieldSettingForAlias($conditionSetting->getAlias());
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