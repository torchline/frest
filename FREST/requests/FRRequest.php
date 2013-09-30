<?php
/**
 * Created by Brad Walker on 6/4/13 at 2:55 PM
*/

require_once(dirname(__FILE__).'/../results/FRErrorResult.php');
require_once(dirname(__FILE__).'/../enums/FRVariableType.php');
require_once(dirname(__FILE__).'/../functions/FRResourceFunction.php');

abstract class FRRequest {

	/** @var FREST */
	protected $frest;
	
	/** @var int */
	protected $resourceID;
	
	/** @var array */
	protected $parameters;
	
	/** @var FRResource */
	protected $resource;

	/** @var FRResult */
	protected $result;

	/** @var string */
	protected $resourceFunctionName;
	
	/** @var FRResourceFunction */
	protected $resourceFunction;
	
	/** @var array */
	protected $resourceFunctionParameters;
	
	
	public $miscParameters = array(
		'method' => TRUE,
		'callback' => TRUE,
		'_' => TRUE,
		'suppress_http_status_codes' => TRUE,
		'access_token' => TRUE
	);



	/**
	 * @param FREST $frest
	 * @param int $resourceID
	 * @param array $parameters
	 * @param string $resourceFunctionName
	 * @param FRResource $resource
	 */
	public function __construct($frest, $resourceID, $parameters, $resourceFunctionName = NULL, $resource = NULL) {
		$this->frest = $frest;
		$this->resourceID = $resourceID;
		$this->parameters = $parameters;
		$this->resourceFunctionName = $resourceFunctionName;

		if (isset($resource)) {
			$this->setupWithResource($resource);
		}
	}



	/**
	 * Initializes the request object with the given resource and
	 * generates all "spec" data (metadata for the query).
	 *
	 * @param \FRResource $resource
	 * @param FRErrorResult $error
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
	 * @return FRResult
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
	 * @param FRErrorResult $error
	 *
	 * @return bool
	 */
	protected function checkForInvalidURLParameters(&$error = NULL) {
		foreach($this->parameters as $parameter=>$value) {
			if (is_array($value)) {
				$error = new FRErrorResult(FRErrorResult::InvalidUsage, 400, "Parameter values (specifically '{$parameter}') are not allowed to be arrays");
				return FALSE;
			}

			$isValidMiscParam = isset($this->miscParameters[$parameter]);

			if (!$isValidMiscParam) {
				$error = new FRErrorResult(FRErrorResult::InvalidField, 400, "Invalid parameter used in query: '{$parameter}'");
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * @param FRErrorResult $error
	 */
	protected function setupResourceFunction(&$error = NULL) {
		
		$resourceFunctions = $this->resource->getResourceFunctions();
		
		// check if valid function name
		if (!isset($resourceFunctions) || !isset($resourceFunctions[$this->resourceFunctionName])) {
			$error = new FRErrorResult(FRErrorResult::ResourceFunctionDoesntExist, 400, "Function name: '{$this->resourceFunctionName}'");
			return;
		}

		/** @var FRResourceFunction $resourceFunction */
		$resourceFunction = $resourceFunctions[$this->resourceFunctionName];
		$resourceFunctionParameters = $resourceFunction->getParameters();
		
		// check method
		$requiredMethod = $resourceFunction->getMethod();
		$currentMethod = $this->frest->getMethod();
		if ($requiredMethod != $currentMethod) {
			$currentMethodString = FRMethod::getString($currentMethod);
			$requiredMethodString = FRMethod::getString($requiredMethod);
			
			$error = new FRErrorResult(FRErrorResult::MismatchingResourceFunctionMethod, 400, "Requires '{$requiredMethodString}' but using '{$currentMethodString}'");
			return;
		}

		// check for invalid parameters and build parameter list for function
		$functionParameters = array();
		foreach ($this->parameters as $parameterName=>$value) {
			$isValidMiscParam = isset($this->miscParameters[$parameterName]);

			if (!$isValidMiscParam) {
				if (!isset($resourceFunctionParameters[$parameterName])) {
					$error = new FRErrorResult(FRErrorResult::InvalidFunctionParameter, 400, "Parameter name: '{$parameterName}'");
					return;
				}

				$functionParameters[$parameterName] = $value;
			}
		}
		
		if (isset($resourceFunctionParameters) && count($resourceFunctionParameters) > 0) {
			// check for all required parameters
			/** @var FRFunctionParameter $parameter */
			$missingParameterNames = array();
			foreach ($resourceFunctionParameters as $parameterName=>$parameter) {
				// check type				
				if (isset($functionParameters[$parameterName])) {
					$variableType = $parameter->getVariableType();
					$value = $functionParameters[$parameterName];
					$castedValue = FRVariableType::castValue($value, $variableType);

					if (!isset($castedValue)) {
						$typeString = FRVariableType::getString($variableType);
						$error = new FRErrorResult(FRErrorResult::InvalidType, 400, "Expecting parameter '{$parameterName}' to be of type '$typeString' but received '{$value}'");
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
				$error = new FRErrorResult(FRErrorResult::MissingRequiredFunctionParameter, 400, "Parameter name: '{$missingString}'");
				return;
			}
		}

		// Check for function implementation existence
		if (!method_exists($this->resource, $this->resourceFunctionName)) {
			$resourceName = get_class($this->resource);
			$error = new FRErrorResult(FRErrorResult::ResourceFunctionMissing, 500, "Function name: '{$this->resourceFunctionName}', resource: '{$resourceName}'");
			return;
		}

		if ($resourceFunction->getRequiresResourceID()) {
			$functionParameters['resourceID'] = $this->resourceID;
		}
		
		$this->resourceFunction = $resourceFunction;
		$this->resourceFunctionParameters = $functionParameters;
	}


	/**
	 * @param bool $forceRegen
	 */
	private function generateResourceFunctionResult($forceRegen = FALSE) {
		// Call function
		$functionName = $this->resourceFunctionName;
		return $this->resource->$functionName($this->resourceFunctionParameters);
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
	 * @return FRResource
	 */
	public function getResource() {
		return $this->resource;
	}

	/**
	 * @return array
	 */
	public function getMiscParameters() {
		return $this->miscParameters;
	}
}