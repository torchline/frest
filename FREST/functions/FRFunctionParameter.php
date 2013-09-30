<?php
/**
 * Created by Brad Walker on 6/8/13 at 12:15 PM
*/

require_once(dirname(__FILE__).'/../enums/FRVariableType.php');

class FRFunctionParameter {

	/** @var int */
	protected $variableType;
	
	/** @var bool */
	protected $required = TRUE;

	
	
	public function __construct($variableType, $required = TRUE) {
		$this->variableType = $variableType;
		$this->required = $required;
	}
	
	

	/**
	 * @return int
	 */
	public function getVariableType() {
		return $this->variableType;
	}

	/**
	 * @return boolean
	 */
	public function getRequired() {
		return $this->required;
	}


	
}