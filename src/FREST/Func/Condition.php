<?php
/**
 * Created by Brad Walker on 6/8/13 at 12:11 PM
*/

namespace FREST\Func;

use FREST\Type;

/**
 * Class Condition
 * @package FREST\Func
 */
class Condition {
	
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
	 * @return Condition
	 */
	public static function GreaterThanFunction() {
		return new Condition(
			'gt',
			'>',
			array(
				new FunctionParam(Type\Variable::FLOAT)
			)
		);
	}

	/**
	 * @return Condition
	 */
	public static function GreaterThanEqualFunction() {
		return new Condition(
			'gte',
			'>=',
			array(
				new FunctionParam(Type\Variable::FLOAT)
			)
		);
	}

	/**
	 * @return Condition
	 */
	public static function LessThanFunction() {
		return new Condition(
			'lt',
			'<',
			array(
				new FunctionParam(Type\Variable::FLOAT)
			)
		);
	}

	/**
	 * @return Condition
	 */
	public static function LessThanEqualFunction() {
		return new Condition(
			'lte',
			'<=',
			array(
				new FunctionParam(Type\Variable::FLOAT)
			)
		);
	}

	/**
	 * @return Condition
	 */
	public static function InFunction() {
		return new Condition(
			'in',
			'IN',
			array(
				new FunctionParam(Type\Variable::ARRAY_STRING)
			)
		);
	}

	/**
	 * @return Condition
	 */
	public static function LikeFunction() {
		return new Condition(
			'like',
			'LIKE',
			array(
				new FunctionParam(Type\Variable::STRING)
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
		/** @var FunctionParam $parameter */
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
			/** @var FunctionParam $parameter */
			$parameter = $this->parameters[$i];
			
			$castedValue = Type\Variable::castValue($value, $parameter->getVariableType());
			
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