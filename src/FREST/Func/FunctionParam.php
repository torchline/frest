<?php
/**
 * Created by Brad Walker on 6/8/13 at 12:15 PM
*/

namespace FREST\Func;

/**
 * Class FunctionParam
 * @package FREST\Func
 */
class FunctionParam {

	/** @var int */
	protected $variableType;
	
	/** @var bool */
	protected $required = TRUE;


	/**
	 * @param int $variableType
	 * @param bool $required
	 */
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