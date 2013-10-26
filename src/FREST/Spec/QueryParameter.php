<?php
/**
 * Created by Brad Walker on 8/14/13 at 3:58 PM
*/

namespace FREST\Spec;

/**
 * Class QueryParameter
 * @package Router\Spec
 */
class QueryParameter {

	/** @var string */
	protected $field;
	
	/** @var string */
	protected $parameterName;
	
	/** @var mixed */
	protected $value;
	
	/** @var int */
	protected $variableType;

	/**
	 * @param string $field
	 * @param string $parameterName
	 * @param string|int|float $value
	 * @param $variableType
	 */
	function __construct($field, $parameterName, $value, $variableType) {
		$this->field = $field;
		$this->parameterName = $parameterName;
		$this->value = $value;
		$this->variableType = $variableType;
	}

	/**
	 * @return string
	 */
	public function getField()
	{
		return $this->field;
	}

	/**
	 * @return string
	 */
	public function getParameterName()
	{
		return $this->parameterName;
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @return int
	 */
	public function getVariableType()
	{
		return $this->variableType;
	}


}