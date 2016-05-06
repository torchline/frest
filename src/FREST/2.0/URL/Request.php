<?php
/**
 * Created by Brad Walker on 5/5/16 at 4:32 PM
*/

namespace FREST\URL;

class Request
{
	/**
	 * @var string
	 */
	protected $resourceName;

	/**
	 * @var mixed
	 */
	protected $resourceID;

	/**
	 * @var string
	 */
	protected $method;

	/**
	 * @var array
	 */
	protected $parameters;
	
	/**
	 * @param string $resourceName
	 * @param mixed $resourceID
	 * @param string $method
	 * @param array $parameters
	 */
	function __construct($method, $resourceName, $resourceID = NULL, $parameters)
	{
		// TODO: safety checking
	 	$this->resourceName = $resourceName;
		$this->resourceID = $resourceID;
		$this->method = $method;
		$this->parameters = $parameters;
	}

	/**
	 * @return string
	 */
	public function getMethod()
	{
		return $this->method;
	}

	/**
	 * @return array
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

	/**
	 * @return mixed
	 */
	public function getResourceID()
	{
		return $this->resourceID;
	}

	/**
	 * @return string
	 */
	public function getResourceName()
	{
		return $this->resourceName;
	}
} 