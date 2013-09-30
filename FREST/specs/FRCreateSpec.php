<?php
/**
 * Created by Brad Walker on 8/15/13 at 10:47 AM
*/

class FRCreateSpec {

	/** @var string */
	protected $alias;
	
	/** @var string */
	protected $field;
	
	/** @var mixed */
	protected $value;
	
	/** @var int */
	protected $variableType;
	

	function __construct($alias, $field, $value, $variableType)
	{
		$this->alias = $alias;
		$this->field = $field;
		$this->value = $value;
		$this->variableType = $variableType;
	}

	/**
	 * @return string
	 */
	public function getAlias()
	{
		return $this->alias;
	}

	/**
	 * @return string
	 */
	public function getField()
	{
		return $this->field;
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