<?php
/**
 * Created by Brad Walker on 6/4/13 at 2:55 PM
*/

namespace FREST\Request;

use FREST;
use FREST\Enum;
use FREST\Func;
use FREST\Result;

require_once(dirname(__FILE__) . '/../Result/Error.php');
require_once(dirname(__FILE__) . '/../Enum/VariableType.php');
require_once(dirname(__FILE__) . '/../Func/Resource.php');

/**
 * Class Request
 * @package FREST\Request
 */
abstract class Request {
	
	/** @var FREST\FREST */
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
	 * @param FREST\FREST $frest
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
	 * @param Result\Error $error
	 */
	public function setupWithResource($resource, &$error = NULL) {
		$this->resource = $resource;

		// Resource Function
		if (isset($this->resourceFunctionName)) {
			$this->setupResourceFunction($error);
			if (isset($error)) {
				return;
			}
		}
		else {
			$this->checkForInvalidURLParameters($error);
			if (isset($error)) {
				return;
			}
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
	 * @param Result\Error $error
	 *
	 * @return bool
	 */
	protected function checkForInvalidURLParameters(&$error = NULL) {
		foreach($this->parameters as $parameter=>$value) {
			$isValid = $this->isValidURLParameter($parameter, $value, $error);
			if (isset($error)) {
				return FALSE;
			}

			if (!$isValid) {
				$error = new Result\Error(Result\Error::InvalidField, 400, "Invalid parameter used in query: '{$parameter}'");
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * @param Result\Error $error
	 */
	protected function setupResourceFunction(&$error = NULL) {
		$resourceFunctions = $this->resource->getResourceFunctions();

		// check if valid Func name
		if (!isset($resourceFunctions) || !isset($resourceFunctions[$this->resourceFunctionName])) {
			$error = new Result\Error(Result\Error::ResourceFunctionDoesntExist, 400, "Function name: '{$this->resourceFunctionName}'");
			return;
		}

		/** @var Func\Resource $resourceFunction */
		$resourceFunction = $resourceFunctions[$this->resourceFunctionName];
		$resourceFunctionParameters = $resourceFunction->getParameters();
		
		// check method
		$requiredMethod = $resourceFunction->getMethod();
		$currentMethod = $this->frest->getMethod();
		if ($requiredMethod != $currentMethod) {
			$currentMethodString = Enum\Method::getString($currentMethod);
			$requiredMethodString = Enum\Method::getString($requiredMethod);
			
			$error = new Result\Error(Result\Error::MismatchingResourceFunctionMethod, 400, "Requires '{$requiredMethodString}' but using '{$currentMethodString}'");
			return;
		}

		// check for invalid parameters and build parameter list for Func
		$functionParameters = array();
		foreach ($this->parameters as $parameterName=>$value) {
			$isValidMiscParam = isset($this->miscParameters[$parameterName]);

			if (!$isValidMiscParam) {
				if (!isset($resourceFunctionParameters[$parameterName])) {
					$error = new Result\Error(Result\Error::InvalidFunctionParameter, 400, "Parameter name: '{$parameterName}'");
					return;
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
					$castedValue = Enum\VariableType::castValue($value, $variableType);

					if (!isset($castedValue)) {
						$typeString = Enum\VariableType::getString($variableType);
						$error = new Result\Error(Result\Error::InvalidType, 400, "Expecting parameter '{$parameterName}' to be of type '$typeString' but received '{$value}'");
						return;
					}

					$functionParameters[$parameterName] = $castedValue;
				}
				else if ($parameter->getRequired()) {
					$missingParameterNames[] = $parameterName;
				}
			}

			if (count($missingParameterNames) > 0) {
				$missingString = implode(', ', $missingParameterNames);
				$error = new Result\Error(Result\Error::MissingRequiredFunctionParameter, 400, "Parameter name: '{$missingString}'");
				return;
			}
		}

		// Check for Func implementation existence
		if (!method_exists($this->resource, $this->resourceFunctionName)) {
			$resourceName = get_class($this->resource);
			$error = new Result\Error(Result\Error::ResourceFunctionMissing, 500, "Function name: '{$this->resourceFunctionName}', resource: '{$resourceName}'");
			return;
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
	 * @param $error
	 * @return bool
	 */
	protected function isValidURLParameter($parameter, $value, &$error) {		
		if (is_array($value)) {
			$error = new Result\Error(Result\Error::InvalidUsage, 400, "Parameter values (specifically '{$parameter}') are not allowed to be arrays");
			return FALSE;
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