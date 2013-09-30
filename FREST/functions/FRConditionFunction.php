<?php
/**
 * Created by Brad Walker on 6/8/13 at 12:11 PM
*/

require_once(dirname(__FILE__) . '/FRFunctionParameter.php');
require_once(dirname(__FILE__) . '/../specs/FRQueryParameterSpec.php');

class FRConditionFunction {
	
	/** @var string */
	protected $name;
	
	/** @var string */
	protected $sqlOperator;
	
	 /** @var array */
	protected $parameters;

	/** @var array */
	protected $replacements = NULL;


	/**
	 * @param string $name
	 * @param string $sqlOperator
	 * @param array $parameters
	 * @param array $replacements
	 */
	public function __construct($name, $sqlOperator, $parameters, $replacements = NULL) {
		$this->name = $name;
		$this->sqlOperator = $sqlOperator;
		$this->parameters = $parameters;
		$this->replacements = $replacements;
	}

	/**
	 * @return FRConditionFunction
	 */
	public static function GreaterThanFunction() {
		return new FRConditionFunction(
			'gt',
			'>',
			array(
				new FRFunctionParameter(FRVariableType::FLOAT)
			)
		);
	}

	/**
	 * @return FRConditionFunction
	 */
	public static function GreaterThanEqualFunction() {
		return new FRConditionFunction(
			'gte',
			'>=',
			array(
				new FRFunctionParameter(FRVariableType::FLOAT)
			)
		);
	}

	/**
	 * @return FRConditionFunction
	 */
	public static function LessThanFunction() {
		return new FRConditionFunction(
			'lt',
			'<',
			array(
				new FRFunctionParameter(FRVariableType::FLOAT)
			)
		);
	}

	/**
	 * @return FRConditionFunction
	 */
	public static function LessThanEqualFunction() {
		return new FRConditionFunction(
			'lte',
			'<=',
			array(
				new FRFunctionParameter(FRVariableType::FLOAT)
			)
		);
	}

	/**
	 * @return FRConditionFunction
	 */
	public static function InFunction() {
		return new FRConditionFunction(
			'in',
			'IN',
			array(
				new FRFunctionParameter(FRVariableType::ARRAY_STRING)
			)
		);
	}

	/**
	 * @return FRConditionFunction
	 */
	public static function LikeFunction() {
		return new FRConditionFunction(
			'like',
			'LIKE',
			array(
				new FRFunctionParameter(FRVariableType::STRING)
			),
			array(
				'~' => '%'
			)
		);
	}


	/**
	 * @param string $valueString
	 * 
	 * @return bool
	 */
	public function validateValue($valueString) {
		$values = explode(',', $valueString);
		$valueCount = count($values);
		
		$minParamsRequired = 0;
		/** @var FRFunctionParameter $parameter */
		foreach ($this->parameters as $parameter) {
			if (!$parameter->getRequired()) {
				break;
			}

			$minParamsRequired++;
		}

		if ($valueCount < $minParamsRequired || $valueCount > count($this->parameters)) {
			return FALSE;
		}

		foreach ($values as $i=>$value) {
			/** @var FRFunctionParameter $parameter */
			$parameter = $this->parameters[$i];
			
			$castedValue = FRVariableType::castValue($value, $parameter->getVariableType());
			
			if (!isset($castedValue)) {
				return FALSE;
			}
		}
		
		return TRUE;
	}
	
	
	/**
	 * @param string $field
	 * @param mixed $value
	 * 
	 * @return string
	 */
	public function generateQueryParamSpec($field, $value) {
		$validValue = $this->validateValue($value);
		if (!$validValue) {
			return NULL;
		}

		if (isset($this->replacements)) {
			foreach ($this->replacements as $search=>$replace) {
				$value = str_replace($search, $replace, $value);
			}
		}

		return "{$field} {$this->sqlOperator} {$value}";
	}

	
	
	
	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getSqlOperator() {
		return $this->sqlOperator;
	}

	/**
	 * @return array
	 */
	public function getParameters() {
		return $this->parameters;
	}

	/**
	 * @return array
	 */
	public function getReplacements() {
		return $this->replacements;
	}
	
}