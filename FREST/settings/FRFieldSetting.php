<?php
/**
 * Created by Brad Walker on 6/5/13 at 12:02 PM
*/

/**
 * Class FRFieldSetting
 */
class FRFieldSetting {
	
	/** @var string */
	protected $field;
	
	/** @var int */
	protected $variableType;


	/**
	 * @param string $field
	 * @param int $variableType
	 */
	public function __construct($field, $variableType) {
		$this->field = $field;
		$this->variableType = $variableType;
	}

	
	/**
	 * @return string
	 */
	public function getField() {
		return $this->field;
	}

	/**
	 * @return int
	 */
	public function getVariableType() {
		return $this->variableType;
	}
	
	
}