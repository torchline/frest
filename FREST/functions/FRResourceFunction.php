<?php
/**
 * Created by Brad Walker on 8/16/13 at 9:08 PM
*/

require_once(dirname(__FILE__) . '/FRFunctionParameter.php');

class FRResourceFunction {
	
	/** @var string */
	protected $name;
	
	/** @var bool */
	protected $requiresResourceID;
	
	/** @var int */
	protected $method;
	
	/** @var array */
	protected $parameters;


	/**
	 * @param string $name function name to be used in query
	 * @param bool $requiresResourceID 
	 * @param int $method FRMethod type
	 * @param array $parameters
	 */
	function __construct($name, $requiresResourceID, $method, $parameters) {
		$this->name = $name;
		$this->requiresResourceID = $requiresResourceID;
		$this->method = $method;
		$this->parameters = $parameters;
	}

	
	
	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return boolean
	 */
	public function getRequiresResourceID() {
		return $this->requiresResourceID;
	}

	/**
	 * @return int
	 */
	public function getMethod() {
		return $this->method;
	}

	/**
	 * @return array
	 */
	public function getParameters() {
		return $this->parameters;
	}


	
}