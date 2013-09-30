<?php 

require_once(dirname(__FILE__).'/../SNOAuth2/SNOAuth2_ClientCredentials.php');
require_once(dirname(__FILE__).'/FRConfig.php');
require_once(dirname(__FILE__).'/resources/FRResource.php');
require_once(dirname(__FILE__).'/enums/FRMethod.php');

require_once(dirname(__FILE__) . '/requests/FRSingleReadRequest.php');
require_once(dirname(__FILE__) . '/requests/FRMultiReadRequest.php');
require_once(dirname(__FILE__) . '/requests/FRCreateRequest.php');
require_once(dirname(__FILE__) . '/requests/FRUpdateRequest.php');
require_once(dirname(__FILE__) . '/requests/FRDeleteRequest.php');

/** 
 * Class FREST
 */
class FREST {

	/** @var FRConfig */
	protected $config;

	/** @var FRRequest */
	protected $request;
	
	/** @var FRResource */
	protected $resource;
	
	/** @var FRErrorResult */
	protected $error;
	
	/** @var int */
	protected $method;
	
	/** @var bool */
	protected $suppressHTTPStatusCodes = FALSE;
	
	/**
	 * @param FRConfig $config
	 * @param string $resourceName The name of the resource for the request (defaults to base name of request url)
	 * @param int|string $resourceID The ID of the resource for the request (defaults to base name of request url if it is an int)
	 * @param array $parameters A list of key-value parameters to pass for the request (defaults to $_GET or $_POST) 
	 * @param string $resourceFunctionName Custom function to be invoked on resource
	 */
	public function __construct($config, $resourceName = NULL, $resourceID = NULL, $parameters = NULL, $resourceFunctionName = NULL) {
		$this->config = $config;
		
		// determine resource name, id, and function
		if (!isset($resourceName) || !isset($resourceID)) {
			$url = $_SERVER['REQUEST_URI'];
			
			$queryPosition = strpos($url, '?');
			if ($queryPosition !== FALSE) {
				$url = substr($url, 0, $queryPosition);
			}
			
			$urlInfo = pathinfo($url);
			
			$urlBaseName = $urlInfo['filename'];
			
			if (!isset($resourceName)) {
				// check if base name is int
				$secondBaseName = basename($urlInfo['dirname']);
				
				if (is_numeric($urlBaseName) && intval($urlBaseName) == $urlBaseName) {
					// assume this int is actually an id and resource is specified in previous path component

					$resourceName = basename($urlInfo['dirname']);
					$resourceID = intval($urlBaseName);
				}
				else if (is_numeric($secondBaseName) && intval($secondBaseName) == $secondBaseName) {
					// assume this int is actually an id and resource precedes it and function follows it

					$resourceName = basename(dirname($urlInfo['dirname']));
					$resourceID = intval($secondBaseName);
					$resourceFunctionName = $urlBaseName;
				}
				else {
					$resourceName = $urlBaseName;
				}
			}
			else if (!isset($resourceID)) {
				$resourceNameIfNoFunctionUsed = basename($urlInfo['dirname']);
				$resourceNameIfFunctionUsed = basename(dirname($urlInfo['dirname']));
				
				if ($resourceNameIfNoFunctionUsed == $resourceName) {
					$resourceID = FRVariableType::castValue($urlBaseName, FRVariableType::INT);
				}
				else if ($resourceNameIfFunctionUsed == $resourceName) {
					$resourceID = FRVariableType::castValue(basename($urlInfo['dirname']), FRVariableType::INT);
					$resourceFunctionName = $urlBaseName;
				}
			}
		}
		
		// determine request method
		$actualMethodString = $_SERVER['REQUEST_METHOD'];
		$actualMethod = $this->getMethodFromString($actualMethodString);

		// check for forced method
		switch ($actualMethod) {
			case FRMethod::GET:
			case FRMethod::POST:
				if ($this->config->getEnableForcedMethod() && isset($_REQUEST['method'])) {
					$forcedMethodString = $_REQUEST['method'];
					$forcedMethod = $this->getMethodFromString($forcedMethodString);

					// if method is valid
					if ($forcedMethod <= 0) {
						$this->error = new FRErrorResult(FRErrorResult::InvalidMethod, 400, "Method '{$forcedMethodString}");
						return;
					}
				}
				break;
		}
		
		if (isset($forcedMethod)) {
			$this->method = $forcedMethod;
			$isMethodForced = TRUE;
		}
		else {
			$this->method = $actualMethod;
			$isMethodForced = FALSE;
		}
		
		// determine parameters to be used for resource
		if (!isset($parameters)) {
			switch ($actualMethod) {
				case FRMethod::GET:
					$parameters = $_GET;
					break;
				case FRMethod::POST:
				case FRMethod::PUT:
				case FRMethod::DELETE:
					$parameters = $_POST;
					break;
				default:
					$this->error = new FRErrorResult(FRErrorResult::InvalidMethod, 400, "Method '{$actualMethodString}");
					return;
					break;
			}
		}
		
		if (isset($parameters['suppress_http_status_codes'])) {
			$value = $parameters['suppress_http_status_codes'];
			$castedValue = FRVariableType::castValue($value, FRVariableType::BOOL);
			if ($castedValue) {
				$this->suppressHTTPStatusCodes = $castedValue;
			}
		}
		
		switch ($this->method) {
			case FRMethod::GET: // read
				if (isset($resourceID)) {
					$this->request = new FRSingleReadRequest($this, $resourceID, $parameters, $resourceFunctionName);
				}
				else {
					$this->request = new FRMultiReadRequest($this, $parameters, $resourceFunctionName);
				}
				break;
			case FRMethod::POST: // create
				$this->request = new FRCreateRequest($this, $resourceID, $parameters, $resourceFunctionName);
				break;
			case FRMethod::PUT: // update / create
				$this->request = new FRUpdateRequest($this, $resourceID, $parameters, $resourceFunctionName);
				break;
			case FRMethod::DELETE: // delete
				$this->request = new FRDeleteRequest($this, $resourceID, $parameters, $resourceFunctionName);
				break;
		}
		
		$this->resource = $this->loadResourceWithName($resourceName, $this->request, $error);
		if (isset($error)) {
			$this->error = $error;
			return;
		}
		
		$this->request->setupWithResource($this->resource, $error);	
		if (isset($error)) {
			$this->error = $error;
			return;
		}
	}

	/**
	 * Outputs the result of the request
	 * 
	 * @param int $format
	 * @param bool $inline
	 * 
	 * @return FRResult
	 */
	public function outputResult($format = FROutputFormat::JSON, $inline = FALSE) {
		if (isset($this->error)) {
			$this->error->output($this, $format, $inline);
			return;
		}
		
		/** @var FRRequest $request */
		$request = $this->request;
		$result = $request->generateResult();
		$result->output($this, $format, $inline);
	}
	
	
	
	// ------------------------------------------
	// --   Helper   ----------------------------
	// ------------------------------------------

	/**
	 * @param string $resourceName
	 * @param FRRequest $request
	 * @param FRErrorResult $error
	 *
	 * @return FRResource
	 */
	public function loadResourceWithName($resourceName, $request, &$error = NULL) {
		$resourceClassName = ucfirst($resourceName);
		$resourcePath = "{$this->config->getResourceDirectory()}/{$resourceClassName}.php";

		// verify resource existence
		if (!file_exists($resourcePath)) {
			$error = new FRErrorResult(FRErrorResult::Config, 500, "Cannot find file for resource '{$resourceName}'");
			return NULL;
		}

		require_once($resourcePath);

		if (!class_exists($resourceClassName, FALSE)) {
			$error = new FRErrorResult(FRErrorResult::Config, 500, "Class '{$resourceClassName}' not found in file '{$resourcePath}'");
			return NULL;
		}
		if (!is_subclass_of($resourceClassName, 'FRResource')) {
			$error = new FRErrorResult(FRErrorResult::Config, 500, "Class '{$resourceClassName}' is not a subclass of resources");
			return NULL;
		}

		/** @var FRResource $resource */
		$resource = new $resourceClassName($this);

		if (method_exists($resource, 'isAuthRequiredForRequest')) {
			$isAuthRequired = $resource->isAuthRequiredForRequest($request, $scopes);

			if ($isAuthRequired) {
				$scopeString = '';
				if (isset($scopes)) {
					$scopeString = implode(' ', $scopes);
				}

				$snoAuth = new SNOAuth2_ClientCredentials(new SNOAuth2Config($this->config->getAuthPDO()));
				$snoAuth->verifyResourceAccess($scopeString);
			}
		}
		
		return $resource;
	}
	
	
	private function getMethodFromString($methodString) {
		$methodString = strtoupper($methodString);
		
		switch ($methodString) {
			case 'GET':
				return FRMethod::GET;
				break;
			case 'POST':
				return FRMethod::POST;
				break;
			case 'PUT':
				return FRMethod::PUT;
				break;
			case 'DELETE':
				return FRMethod::DELETE;
				break;
		}
		
		return -1;
	}

	
	
	
	
	
	
	/**
	 * @return \FRConfig
	 */
	public function getConfig() {
		return $this->config;
	}

	/**
	 * @return \FRRequest
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * @return \FRResource
	 */
	public function getResource() {
		return $this->resource;
	}

	/**
	 * @return int
	 */
	public function getMethod()
	{
		return $this->method;
	}

	/**
	 * @return boolean
	 */
	public function getSuppressHTTPStatusCodes()
	{
		return $this->suppressHTTPStatusCodes;
	}
	
	
}