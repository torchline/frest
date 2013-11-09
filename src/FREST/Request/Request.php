<?php
/**
 * Created by Brad Walker on 6/4/13 at 2:55 PM
*/

namespace FREST\Request;

use FREST;
use FREST\Type;
use FREST\Func;
use FREST\Result;

/**
 * Class Request
 * @package FREST\Request
 */
abstract class Request {
	
	/** @var FREST\Router */
	protected $frest;
	
	/** @var int */
	protected $resourceID;
	
	/** @var array */
	protected $parameters;
	
	/** @var \FREST\Resource */
	protected $resource;

	/** @var Result\Result */
	protected $result;

	/** @var string */
	protected $resourceFunctionName;
	
	/** @var Func\Resource */
	protected $resourceFunction;
	
	/** @var array */
	protected $resourceFunctionParameters;

	/** @var bool */
	protected $wasInternallyLoaded;

	/** @var array */
	protected $miscParameters;

	/**
	 * @param FREST\Router $frest
	 * @param int $resourceID
	 * @param array $parameters
	 * @param string $resourceFunctionName
	 */
	public function __construct($frest, $resourceID, $parameters, $resourceFunctionName = NULL) {
		$this->frest = $frest;
		$this->resourceID = $resourceID;
		$this->parameters = $parameters;
		$this->resourceFunctionName = $resourceFunctionName;

		$this->miscParameters['method'] = TRUE;
		$this->miscParameters['callback'] = TRUE;
		$this->miscParameters['_'] = TRUE;
		$this->miscParameters['suppress_http_status_codes'] = TRUE;
		$this->miscParameters['access_token'] = TRUE;
	}



	/**
	 * Initializes the request object with the given resource and
	 * generates all "spec" data (metadata for the query).
	 *
	 * @param FREST\Resource $resource
	 */
	public function setupWithResource($resource) {
		$this->resource = $resource;

		// Resource Function
		if (isset($this->resourceFunctionName)) {
			$this->setupResourceFunction();
		}
		else {
			$this->checkForInvalidURLParameters();
		}
	}




	/**
	 * Performs the query on the resource and returns the result
	 *
	 * @param bool $forceRegen
	 *
	 * @return Result\Result
	 */
	public function generateResult($forceRegen = FALSE) {
		if (isset($this->result) && !$forceRegen) {
			return $this->result;
		}
		
		if (isset($this->resourceFunction)) {
			$this->result = $this->generateResourceFunctionResult($forceRegen);
			return $this->result;
		}
		
		return NULL;
	}
	
	
	
	/**
	 * @return bool
	 * @throws FREST\Exception
	 */
	protected function checkForInvalidURLParameters() {
		foreach($this->parameters as $parameter=>$value) {
			$isValid = $this->isValidURLParameter($parameter, $value);

			if (!$isValid) {
				throw new FREST\Exception(FREST\Exception::InvalidField, "Invalid parameter used in query: '{$parameter}'");
			}
		}

		return TRUE;
	}

	/**
	 * @throws FREST\Exception
	 */
	protected function setupResourceFunction() {
		$resourceFunctions = $this->resource->getResourceFunctions();

		// check if valid Func name
		if (!isset($resourceFunctions) || !isset($resourceFunctions[$this->resourceFunctionName])) {
			throw new FREST\Exception(FREST\Exception::ResourceFunctionDoesntExist, "Function name: '{$this->resourceFunctionName}'");
		}

		/** @var Func\Resource $resourceFunction */
		$resourceFunction = $resourceFunctions[$this->resourceFunctionName];
		$resourceFunctionParameters = $resourceFunction->getParameters();
		
		// check method
		$requiredMethod = $resourceFunction->getMethod();
		$currentMethod = $this->frest->getMethod();
		if ($requiredMethod != $currentMethod) {
			$currentMethodString = Type\Method::getString($currentMethod);
			$requiredMethodString = Type\Method::getString($requiredMethod);

			throw new FREST\Exception(FREST\Exception::MismatchingResourceFunctionMethod, "Requires '{$requiredMethodString}' but using '{$currentMethodString}'");
		}

		// check for invalid parameters and build parameter list for Func
		$functionParameters = array();
		foreach ($this->parameters as $parameterName=>$value) {
			$isValidMiscParam = isset($this->miscParameters[$parameterName]);

			if (!$isValidMiscParam) {
				if (!isset($resourceFunctionParameters[$parameterName])) {
					throw new FREST\Exception(FREST\Exception::InvalidFunctionParameter, "Parameter name: '{$parameterName}'");
				}

				$functionParameters[$parameterName] = $value;
			}
		}
		
		if (isset($resourceFunctionParameters) && count($resourceFunctionParameters) > 0) {
			// check for all required parameters
			/** @var Func\FunctionParam $parameter */
			$missingParameterNames = array();
			foreach ($resourceFunctionParameters as $parameterName=>$parameter) {
				// check type				
				if (isset($functionParameters[$parameterName])) {
					$variableType = $parameter->getVariableType();
					$value = $functionParameters[$parameterName];
					$castedValue = Type\Variable::castValue($value, $variableType);

					if (!isset($castedValue)) {
						$typeString = Type\Variable::getString($variableType);
						throw new FREST\Exception(FREST\Exception::InvalidType, "Expecting parameter '{$parameterName}' to be of type '$typeString' but received '{$value}'");
					}

					$functionParameters[$parameterName] = $castedValue;
				}
				else if ($parameter->getRequired()) {
					$missingParameterNames[] = $parameterName;
				}
			}

			if (count($missingParameterNames) > 0) {
				$missingString = implode(', ', $missingParameterNames);
				throw new FREST\Exception(FREST\Exception::MissingRequiredFunctionParameter, "Parameter name: '{$missingString}'");
			}
		}

		// Check for Func implementation existence
		if (!method_exists($this->resource, $this->resourceFunctionName)) {
			$resourceName = get_class($this->resource);
			throw new FREST\Exception(FREST\Exception::ResourceFunctionMissing, "Function name: '{$this->resourceFunctionName}', resource: '{$resourceName}'");
		}

		if ($resourceFunction->getRequiresResourceID()) {
			$functionParameters['resourceID'] = $this->resourceID;
		}
		
		$this->resourceFunction = $resourceFunction;
		$this->resourceFunctionParameters = $functionParameters;
	}


	/**
	 * @return string|int|float
	 */
	private function generateResourceFunctionResult() {
		// Call Func
		$functionName = $this->resourceFunctionName;
		return $this->resource->$functionName($this->resourceFunctionParameters);
	}

	/**
	 * @param $parameter
	 * @param $value
	 * @return bool
	 * @throws FREST\Exception
	 */
	protected function isValidURLParameter($parameter, $value) {
		if (is_array($value)) {
			throw new FREST\Exception(FREST\Exception::InvalidUsage, "Parameter values (specifically '{$parameter}') are not allowed to be arrays");
		}
		else {
			$isValid = isset($this->miscParameters[$parameter]);
		}
		
		return $isValid;
	}
	
	
	// ------------------------------------------
	// --   Getters   ---------------------------
	// ------------------------------------------
	
	/**
	 * @return array
	 */
	public function getParameters() {
		return $this->parameters;
	}

	/**
	 * @return Resource
	 */
	public function getResource() {
		return $this->resource;
	}

	/**
	 * @param boolean $wasInternallyLoaded
	 */
	public function setWasInternallyLoaded($wasInternallyLoaded)
	{
		$this->wasInternallyLoaded = $wasInternallyLoaded;
	}

	/**
	 * @return boolean
	 */
	public function getWasInternallyLoaded()
	{
		return $this->wasInternallyLoaded;
	}
	
	
}