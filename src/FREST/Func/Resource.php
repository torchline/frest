<?php
/**
 * Created by Brad Walker on 8/16/13 at 9:08 PM
*/

namespace FREST\Func;

/**
 * Class Resource
 * @package FREST\Func
 */
class Resource {
	
	/** @var string */
	protected $name;
	
	/** @var bool */
	protected $requiresResourceID;
	
	/** @var int */
	protected $method;
	
	/** @var array */
	protected $parameters;


	/**
	 * @param string $name Func name to be used in query
	 * @param bool $requiresResourceID 
	 * @param int $method Type\Method type
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