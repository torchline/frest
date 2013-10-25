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
	 * @param FREST $frest
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
	 */
	public function setupWithResource($resource) {
		parent::setupWithResource($resource);
		
		$this->orderSpecs = $this->generateOrderSpecs();
		$this->conditionSpecs = $this->generateConditionSpecs();
		$this->queryParameterSpecs = NULL; // built in generateResult along with conditionString
	}

	/**
	 * @param bool $forceRegen
	 * @return Result\PluralRead|Result\Error
	 * @throws FREST\Exception
	 */
	public function generateResult($forceRegen = FALSE) {
		$this->frest->startTimingForLabel(Enum\Timing::PROCESSING, 'pluralread');

		$otherResult = parent::generateResult($forceRegen);
		if (isset($otherResult)) {
			return $otherResult;
		}

		$fieldString = $this->generateFieldString($this->fieldSpecs);
		$tablesToReadString = $this->generateTableString($this->tableSpecs);
		$conditionString = $this->processConditionSpecs($this->conditionSpecs, $this->queryParameterSpecs);
		$offset = $this->generateOffset($this->queryParameterSpecs);
		$limit = $this->generateLimit($this->resource, $this->queryParameterSpecs);
		$orderByString = $this->generateOrderString($this->orderSpecs);
		$joinsString = $this->generateJoinString($this->resource, $this->joinSpecs);
		
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
			throw new FREST\Exception(FREST\Exception::SQLError, 'Error querying database for Result');
		}

		$objects = $resultsStmt->fetchAll(\PDO::FETCH_OBJ);

		if (!$countStmt->execute()) {
			throw new FREST\Exception(FREST\Exception::SQLError, 'Error querying database for count');
		}

		$countResult = $countStmt->fetchAll(\PDO::FETCH_ASSOC);
		$count = intval($countResult[0]['Count']);

		$this->frest->stopTimingForLabel(Enum\Timing::SQL, 'pluralread');

		$this->parseObjects($this->resource, $objects, $this->readSettings);
		$this->result = new Result\PluralRead($objects, $limit, $offset, $count);

		return $this->result;
	}
	
	
	/** 
	 * @return array
	 */
	private function generateConditionSpecs() {
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
	 * @return string
	 * @throws FREST\Exception
	 */
	private function processConditionSpecs($conditionSpecs, &$queryParameterSpecs) {
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
					$parsedValueVariableType
				);
				
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
							throw new FREST\Exception(FREST\Exception::Config, "Invalid type set for condition '{$alias}': '{$variableTypeString}'");
							break;
							
						default:
							break;
					}

					$castedValue = Enum\VariableType::castValue($conditionSpec->getValue(), $variableType);
					if (!isset($castedValue)) {
						$variableTypeString = Enum\VariableType::getString($variableType);
						throw new FREST\Exception(FREST\Exception::InvalidType, "Expecting field '{$alias}' to be of type '{$variableTypeString}' but received '{$conditionSpec->getValue()}'");
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
	 * @return int
	 * @throws FREST\Exception
	 */
	private function generateOffset(&$queryParameterSpecs) {
		$offset = isset($this->parameters['offset']) ? $this->parameters['offset'] : 0;
		
		$castedOffset = Enum\VariableType::castValue($offset, Enum\VariableType::INT);
		if (!isset($castedOffset)) {
			$typeString = Enum\VariableType::getString(Enum\VariableType::INT);
			throw new FREST\Exception(FREST\Exception::InvalidType, "Expecting offset to be of type '{$typeString}' but received '{$offset}'");
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
	 * @return int
	 * @throws FREST\Exception
	 */
	private function generateLimit($resource, &$queryParameterSpecs) {
		$limit = isset($this->parameters['limit']) ? $this->parameters['limit'] : $resource->getDefaultLimit();

		$castedLimit = Enum\VariableType::castValue($limit, Enum\VariableType::INT);
		if (!isset($castedLimit)) {
			$typeString = Enum\VariableType::getString(Enum\VariableType::INT);
			throw new FREST\Exception(FREST\Exception::InvalidType, "Expecting offset to be of type '{$typeString}' but received '{$limit}'.");
		}
		
		$maxLimit = $resource->getMaxLimit();
		if ($castedLimit > $maxLimit) {
			throw new FREST\Exception(FREST\Exception::InvalidValue, "The limit for this resource must not exceed {$maxLimit}. A limit of {$castedLimit} was supplied.");
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
	 * @return array
	 * @throws FREST\Exception
	 */
	private function generateOrderSpecs() {
		$orderSpecs = array();
		
		if (isset($this->parameters['orderBy'])) {
			$orderByParameterString = $this->parameters['orderBy'];
			
			if (strlen($orderByParameterString) == 0) {
				throw new FREST\Exception(FREST\Exception::InvalidValue, "An empty value was supplied to 'orderBy'");
			}

			$orderByParameters = explode(',', $orderByParameterString);

			$orderSettings = $this->resource->getOrderSettings();
			
			foreach ($orderByParameters as $orderByParam) {
				$pieces = explode(':', trim($orderByParam));
				$alias = trim($pieces[0]);
				
				$fieldSetting = $this->resource->getFieldSettingForAlias($alias);
				if (!isset($fieldSetting)) {
					throw new FREST\Exception(FREST\Exception::InvalidValue, "The resource does not have a field named '{$alias}'");
				}
				
				if (!isset($orderSettings[$alias])) {
					throw new FREST\Exception(FREST\Exception::InvalidValue, "The resource does not allow ordering with '{$alias}'");
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
						throw new FREST\Exception(FREST\Exception::InvalidValue, "Order direction '{$userDirection}' is not valid");
					}
				}
				else {
					if (!$orderSetting->getAscendingEnabled() && !$orderSetting->getDescendingEnabled()) {
						throw new FREST\Exception(FREST\Exception::InvalidValue, "The resource does not allow ordering with '{$alias}'");
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
	 * @return string
	 */
	private function generateOrderString($orderSpecs) {
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
	 * 
	 * @return Func\Condition
	 * @throws FREST\Exception
	 */
	private function checkForFunctions($valueToCheck, $valueVariableType, $functions, &$parsedValue, &$parsedValueVariableType) {
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
					throw new FREST\Exception(FREST\Exception::InvalidValue, "Empty value specified in Func '{$functionName}'");
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
					throw new FREST\Exception(FREST\Exception::InvalidType, "Expecting value for Func '{$functionName}' to be of type '{$variableTypeString}' but received '{$innerValue}'");
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
	 * @return bool
	 * @throws FREST\Exception
	 */
	protected function isValidURLParameter($parameter, $value) {
		/** @noinspection PhpUndefinedClassInspection */
		$isValid = parent::isValidURLParameter($parameter, $value);	
		if (!$isValid) { // if not already determined to be valid
			$conditionSettings = $this->resource->getConditionSettings();
			
			if (isset($conditionSettings[$parameter])) {
				/** @var Setting\Condition $conditionSetting */
				$conditionSetting = $conditionSettings[$parameter];
				
				$fieldSetting = $this->resource->getFieldSettingForAlias($conditionSetting->getAlias());
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