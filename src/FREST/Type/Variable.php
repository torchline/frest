<?php
/**
 * Created by Brad Walker on 6/4/13 at 4:34 PM
*/

namespace FREST\Type;

/**
 * Class Variable
 * @package FREST\Type
 */
final class Variable {
	const BOOL = 1;
	const INT = 2;
	const FLOAT = 3;
	const STRING = 4;
	
	const ARRAY_BOOL = 5;
	const ARRAY_INT = 6;
	const ARRAY_FLOAT = 7;
	const ARRAY_STRING = 8;

	/**
	 *
	 */
	private function __construct() {}

	/**
	 * @param int $varType
	 * 
	 * @return string
	 */
	public static function getString($varType) {
		switch ($varType) {
			case self::BOOL:
				return 'bool';
				break;
			case self::INT:
				return 'int';
				break;
			case self::FLOAT:
				return 'float';
				break;
			case self::STRING:
				return 'string';
				break;

			case self::ARRAY_BOOL:
				return 'array:bool';
				break;
			case self::ARRAY_INT:
				return 'array:int';
				break;
			case self::ARRAY_FLOAT:
				return 'array:float';
				break;
			case self::ARRAY_STRING:
				return 'array:string';
				break;
			
			default:
				return NULL;
				break;
		}
	}

	
	/**
	 * @param int $arrayVariableType
	 * @return int
	 */
	public static function arrayElementVariableType($arrayVariableType) {
		switch ($arrayVariableType) {
			case self::ARRAY_BOOL:
				return Variable::BOOL;
				break;
			case self::ARRAY_INT:
				return Variable::INT;
				break;
			case self::ARRAY_FLOAT:
				return Variable::FLOAT;
				break;
			case self::ARRAY_STRING:
				return Variable::STRING;
				break;
		}
		
		return 0;
	}


	/**
	 * @param int $variableType
	 * @return int
	 */
	public static function arrayVariableTypeFromElement($variableType) {
		switch ($variableType) {
			case self::BOOL:
				return Variable::ARRAY_BOOL;
				break;
			case self::INT:
				return Variable::ARRAY_INT;
				break;
			case self::FLOAT:
				return Variable::ARRAY_FLOAT;
				break;
			case self::STRING:
				return Variable::ARRAY_STRING;
				break;
		}

		return 0;
	}
	

	/**
	 * @param int $variableType1
	 * @param int $variableType2
	 * @return int
	 */
	public static function stricterVariableType($variableType1, $variableType2) {
		$isVar1Array = FALSE;
		$var1ElementVariableType = $variableType1;
		switch ($variableType1) {
			case Variable::ARRAY_BOOL:
			case Variable::ARRAY_INT:
			case Variable::ARRAY_FLOAT:
			case Variable::ARRAY_STRING:
				$isVar1Array = TRUE;
				$var1ElementVariableType = Variable::arrayElementVariableType($variableType1);
				break;
		}

		$isVar2Array = FALSE;
		$var2ElementVariableType = $variableType2;
		switch ($variableType2) {
			case Variable::ARRAY_BOOL:
			case Variable::ARRAY_INT:
			case Variable::ARRAY_FLOAT:
			case Variable::ARRAY_STRING:
				$isVar2Array = TRUE;
				$var2ElementVariableType = Variable::arrayElementVariableType($variableType2);
				break;
		}
		
		if ($isVar1Array == $isVar2Array) {
			return $variableType1 > $variableType2 ? $variableType2 : $variableType1;
		}
		else if ($isVar1Array && !$isVar2Array) {
			$stricterElementType = Variable::stricterVariableType($var1ElementVariableType, $variableType2);
			return Variable::arrayVariableTypeFromElement($stricterElementType);
		}
		else {
			$stricterElementType = Variable::stricterVariableType($variableType1, $var2ElementVariableType);
			return Variable::arrayVariableTypeFromElement($stricterElementType);
		}
	}

	/**
	 * Returns the supplied value after checking it against the supplied type
	 * and performing any necessary operations on it. If the value is found
	 * not to be of the supplied type, NULL is returned.
	 *
	 * @param mixed $value
	 * @param int $variableType
	 *
	 * @return mixed|NULL The converted (if necessary) value or NULL if not of correct type
	 */
	public static function castValue($value, $variableType) {
		switch ($variableType) {
			case Variable::BOOL:
				if ($value === FALSE ||
					$value === 0 ||
					$value === '0' ||
					strcasecmp($value, 'false') === 0 ||
					strcasecmp($value, 'f') === 0 ||
					strcasecmp($value, 'no') === 0 ||
					strcasecmp($value, 'n') === 0
				) {
					return FALSE;
				}
				
				if ($value === TRUE ||
					$value === 1 ||
					$value === '1' ||
					strcasecmp($value, 'true') === 0 ||
					strcasecmp($value, 't') === 0 ||
					strcasecmp($value, 'yes') === 0 ||
					strcasecmp($value, 'y') === 0
				) {
					return TRUE;
				}

				return NULL;
				break;
			case Variable::INT:
				$valueIntVal = intval($value);
				return is_numeric($value) && $valueIntVal == $value ? $valueIntVal : NULL;
				break;
			case Variable::FLOAT:
				return is_numeric($value) ? floatval($value) : NULL;
				break;
			case Variable::STRING:
				return (string)$value;
				break;
			case Variable::ARRAY_BOOL:
			case Variable::ARRAY_INT:
			case Variable::ARRAY_FLOAT:
			case Variable::ARRAY_STRING:
				// check sub values against arrayType
				$valueArray = explode(',', $value);
				$elementType = Variable::arrayElementVariableType($variableType);
				
				foreach ($valueArray as $i=>$arrayValue) {
					if (strlen($arrayValue) == 0) {
						return NULL;
					}
	
					$newArrayValue = Variable::castValue($arrayValue, $elementType);
					if (!isset($newArrayValue)) {
						return NULL;
					}
	
					$valueArray[$i] = $newArrayValue;
				}

				return $valueArray;
				break;
		}

		return NULL;
	}
	
	
	/**
	 * @param int $variableType
	 * @return int
	 */
	public static function pdoTypeFromVariableType($variableType) {
		switch ($variableType) {
			case self::BOOL:
				return \PDO::PARAM_BOOL;
				break;
			case self::INT:
				return \PDO::PARAM_INT;
				break;
			case self::FLOAT:
				return \PDO::PARAM_STR;
				break;
			case self::STRING:
				return \PDO::PARAM_STR;
				break;

			case self::ARRAY_BOOL:
				return \PDO::PARAM_STR;
				break;
			case self::ARRAY_INT:
				return \PDO::PARAM_STR;
				break;
			case self::ARRAY_FLOAT:
				return \PDO::PARAM_STR;
				break;
			case self::ARRAY_STRING:
				return \PDO::PARAM_STR;
				break;
		}

		return \PDO::PARAM_STR;
	}
}