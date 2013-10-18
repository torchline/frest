<?php
/**
 * Created by Brad Walker on 6/4/13 at 4:34 PM
*/

final class FRVariableType {
	const BOOL = 1;
	const INT = 2;
	const FLOAT = 3;
	const STRING = 4;
	
	const ARRAY_BOOL = 5;
	const ARRAY_INT = 6;
	const ARRAY_FLOAT = 7;
	const ARRAY_STRING = 8;

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
		}
	}

	
	/**
	 * @param int $arrayVariableType
	 * @return int
	 */
	public static function arrayElementVariableType($arrayVariableType) {
		switch ($arrayVariableType) {
			case self::ARRAY_BOOL:
				return FRVariableType::BOOL;
				break;
			case self::ARRAY_INT:
				return FRVariableType::INT;
				break;
			case self::ARRAY_FLOAT:
				return FRVariableType::FLOAT;
				break;
			case self::ARRAY_STRING:
				return FRVariableType::STRING;
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
				return FRVariableType::ARRAY_BOOL;
				break;
			case self::INT:
				return FRVariableType::ARRAY_INT;
				break;
			case self::FLOAT:
				return FRVariableType::ARRAY_FLOAT;
				break;
			case self::STRING:
				return FRVariableType::ARRAY_STRING;
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
			case FRVariableType::ARRAY_BOOL:
			case FRVariableType::ARRAY_INT:
			case FRVariableType::ARRAY_FLOAT:
			case FRVariableType::ARRAY_STRING:
				$isVar1Array = TRUE;
				$var1ElementVariableType = FRVariableType::arrayElementVariableType($variableType1);
				break;
		}

		$isVar2Array = FALSE;
		$var2ElementVariableType = $variableType2;
		switch ($variableType2) {
			case FRVariableType::ARRAY_BOOL:
			case FRVariableType::ARRAY_INT:
			case FRVariableType::ARRAY_FLOAT:
			case FRVariableType::ARRAY_STRING:
				$isVar2Array = TRUE;
				$var2ElementVariableType = FRVariableType::arrayElementVariableType($variableType2);
				break;
		}
		
		if ($isVar1Array == $isVar2Array) {
			return $variableType1 > $variableType2 ? $variableType2 : $variableType1;
		}
		else if ($isVar1Array && !$isVar2Array) {
			$stricterElementType = FRVariableType::stricterVariableType($var1ElementVariableType, $variableType2);
			return FRVariableType::arrayVariableTypeFromElement($stricterElementType);
		}
		else {
			$stricterElementType = FRVariableType::stricterVariableType($variableType1, $var2ElementVariableType);
			return FRVariableType::arrayVariableTypeFromElement($stricterElementType);
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
			case FRVariableType::BOOL:
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
			case FRVariableType::INT:
				$valueIntVal = intval($value);
				return is_numeric($value) && $valueIntVal == $value ? $valueIntVal : NULL;
				break;
			case FRVariableType::FLOAT:
				return is_numeric($value) ? floatval($value) : NULL;
				break;
			case FRVariableType::STRING:
				return (string)$value;
				break;
			case FRVariableType::ARRAY_BOOL:
			case FRVariableType::ARRAY_INT:
			case FRVariableType::ARRAY_FLOAT:
			case FRVariableType::ARRAY_STRING:
				// check sub values against arrayType
				$valueArray = explode(',', $value);
				$elementType = FRVariableType::arrayElementVariableType($variableType);
				
				foreach ($valueArray as $i=>$arrayValue) {
					if (strlen($arrayValue) == 0) {
						return NULL;
					}
	
					$newArrayValue = FRVariableType::castValue($arrayValue, $elementType);
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
				return PDO::PARAM_BOOL;
				break;
			case self::INT:
				return PDO::PARAM_INT;
				break;
			case self::FLOAT:
				return PDO::PARAM_STR;
				break;
			case self::STRING:
				return PDO::PARAM_STR;
				break;

			case self::ARRAY_BOOL:
				return PDO::PARAM_STR;
				break;
			case self::ARRAY_INT:
				return PDO::PARAM_STR;
				break;
			case self::ARRAY_FLOAT:
				return PDO::PARAM_STR;
				break;
			case self::ARRAY_STRING:
				return PDO::PARAM_STR;
				break;
		}

		return PDO::PARAM_STR;
	}
}