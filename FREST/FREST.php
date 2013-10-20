<?php

namespace FREST;

require_once(dirname(__FILE__).'/../SNOAuth2/SNOAuth2_ClientCredentials.php');
require_once(dirname(__FILE__) . '/Config.php');
require_once(dirname(__FILE__) . '/Resource.php');
require_once(dirname(__FILE__) . '/Enum/Method.php');
require_once(dirname(__FILE__) . '/Enum/Timing.php');

require_once(dirname(__FILE__) . '/Request/SingularRead.php');
require_once(dirname(__FILE__) . '/Request/PluralRead.php');
require_once(dirname(__FILE__) . '/Request/Create.php');
require_once(dirname(__FILE__) . '/Request/Update.php');
require_once(dirname(__FILE__) . '/Request/Delete.php');

/** 
 * Class FREST
 */
class FREST {

	const FORCED_NULL = '__NULL__';
	
	/** @var \stdClass */
	protected $timingObject;
	
	/** @var array */
	protected $startTimes;
	
	/** @var Config */
	protected $config;

	/** @var Request\Request */
	protected $request;
	
	/** @var \FREST\Resource */
	protected $resource;
	
	/** @var Result\Error */
	protected $error;
	
	/** @var int */
	protected $method;
	
	/** @var bool */
	protected $suppressHTTPStatusCodes = FALSE;
	
	/** @var array */
	protected $loadedResources = array();
	
	/**
	 * @param Config $config defaults to Config::fromFile('config.php')
	 * @param string $resourceName The name of the resource for the request (defaults to base name of request url)
	 * @param int|string $resourceID The ID of the resource for the request (defaults to base name of request url if it is an int)
	 * @param array $parameters A list of key-value parameters to pass for the request (defaults to $_GET or $_POST) 
	 * @param int $requestMethod The method (get, post, put, delete) (e.g. Enum\Method) of the request (defaults to REQUEST_METHOD)
	 * @param string $resourceFunctionName Custom Func to be invoked on resource
	 */
	public function __construct($config = NULL, $resourceName = NULL, $resourceID = NULL, $parameters = NULL, $requestMethod = NULL, $resourceFunctionName = NULL) {
		$this->startTimingForLabel(Enum\Timing::TOTAL, 'frest');
		
		if (!isset($config)) {
			$config = Config::fromFile($this);
		}
		$this->config = $config;
		
		$this->suppressHTTPStatusCodes = $this->config->getSuppressHTTPStatusCodes();

		$this->startTimingForLabel(Enum\Timing::SETUP, 'frest');
		
		// determine resource name, id, and Func (if any, taking into account if any of those were passed as parameters)
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
					// assume this int is actually an id and resource precedes it and Func follows it
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
					$resourceID = $urlBaseName;
				}
				else if ($resourceNameIfFunctionUsed == $resourceName) {
					$resourceID = basename($urlInfo['dirname']);
					$resourceFunctionName = $urlBaseName;
				}
			}
		}
		
		// cast resource ID if exists
		if (isset($resourceID)) {
			$resourceIDType = $this->resourceIDTypeFromResource($resourceName, $error);
			if (isset($error)) {
				$this->error = $error;
				return;
			}

			$resourceID = Enum\VariableType::castValue($resourceID, $resourceIDType);
		}
					
		// determine request method
		if (!isset($requestMethod)) {
			$actualMethodString = $_SERVER['REQUEST_METHOD'];
			$actualMethod = Enum\Method::fromString($actualMethodString);
		}
		else {
			$actualMethod = $requestMethod;
		}
		
		// check for forced method
		switch ($actualMethod) {
			case Enum\Method::GET:
			case Enum\Method::POST:
				if ($this->config->getEnableForcedMethod() && isset($_REQUEST['method'])) {
					$forcedMethodString = $_REQUEST['method'];
					$forcedMethod = Enum\Method::fromString($forcedMethodString);

					// if method is valid
					if ($forcedMethod <= 0) {
						$this->error = new Result\Error(Result\Error::InvalidMethod, 400, "Method '{$forcedMethodString}");
						return;
					}
				}
				break;
		}
		
		if (isset($forcedMethod)) {
			$this->method = $forcedMethod;
		}
		else {
			$this->method = $actualMethod;
		}
		
		// determine parameters to be used for resource
		if (!isset($parameters)) {
			switch ($actualMethod) {
				case Enum\Method::GET:
					$parameters = $_GET;
					break;
				case Enum\Method::POST:
				case Enum\Method::PUT:
				case Enum\Method::DELETE:
					$parameters = $_POST;
					break;
				default:
					$methodString = Enum\Method::getString($actualMethod);
					$this->error = new Result\Error(Result\Error::InvalidMethod, 400, "Method '{$methodString}");
					return;
					break;
			}
		}
		
		if (isset($parameters['suppress_http_status_codes'])) {
			$value = $parameters['suppress_http_status_codes'];
			$this->suppressHTTPStatusCodes = Enum\VariableType::castValue($value, Enum\VariableType::BOOL);
		}
		
		switch ($this->method) {
			case Enum\Method::GET: // read
				if (isset($resourceID) && $resourceID != self::FORCED_NULL) {
					$this->request = new Request\SingularRead($this, $resourceID, $parameters, $resourceFunctionName);
				}
				else {
					$this->request = new Request\PluralRead($this, $parameters, $resourceFunctionName);
				}
				break;
			case Enum\Method::POST: // create
				$this->request = new Request\Create($this, $resourceID, $parameters, $resourceFunctionName);
				break;
			case Enum\Method::PUT: // update / create
				$this->request = new Request\Update($this, $resourceID, $parameters, $resourceFunctionName);
				break;
			case Enum\Method::DELETE: // delete
				$this->request = new Request\Delete($this, $resourceID, $parameters, $resourceFunctionName);
				break;
			
			default:
				break;
		}
		
		$this->resource = $this->loadResourceWithName($resourceName, $this->request, $error);
		if (isset($error)) {
			$this->error = $error;
			return;
		}

		$this->stopTimingForLabel(Enum\Timing::SETUP, 'frest');
		$this->startTimingForLabel(Enum\Timing::PROCESSING, 'frest');
		
		$this->request->setupWithResource($this->resource, $error);	
		if (isset($error)) {
			$this->error = $error;
			return;
		}

		$this->stopTimingForLabel(Enum\Timing::PROCESSING, 'frest');
		$this->stopTimingForLabel(Enum\Timing::TOTAL, 'frest');
	}

	/**
	 * @return FREST
	 */
	public static function automatic() {
		return new FREST();
	}

	/**
	 * @param string $resourceName
	 * @param string|int $resourceID
	 * @param array $parameters
	 * @param int $requestMethod
	 * @param string $resourceFunctionName
	 * @return FREST
	 */
	public static function all($resourceName = NULL, $resourceID = NULL, $parameters = NULL, $requestMethod = NULL, $resourceFunctionName = NULL) {
		return new FREST(NULL, $resourceName, $resourceID, $parameters, $requestMethod, $resourceFunctionName);
	}

	/**
	 * @param string|int $id
	 * @param string $resourceName
	 * @param array $parameters
	 * @param int $requestMethod
	 * @param string $resourceFunctionName
	 * @return FREST
	 */
	public static function singular($id, $resourceName = NULL, $parameters = NULL, $requestMethod = NULL, $resourceFunctionName = NULL) {
		return new FREST(NULL, $resourceName, $id, $parameters, $requestMethod, $resourceFunctionName);
	}

	/**
	 * @param string $resourceName
	 * @param array $parameters
	 * @param int $requestMethod
	 * @param string $resourceFunctionName
	 * @return FREST
	 */
	public static function plural($resourceName = NULL, $parameters = NULL, $requestMethod = NULL, $resourceFunctionName = NULL) {
		return new FREST(NULL, $resourceName, self::FORCED_NULL, $parameters, $requestMethod, $resourceFunctionName);
	}
	
		

	/**
	 * Outputs the result of the request
	 * 
	 * @param int $format
	 * @param bool $inline
	 * 
	 * @return mixed
	 */
	public function outputResult($format = Enum\OutputFormat::JSON, $inline = FALSE) {
		if (isset($this->error)) {
			$output = $this->error->output($this, $format, $inline);
		}
		else {
			$this->startTimingForLabel('total', 'frest');
			$result = $this->request->generateResult();
			$this->stopTimingForLabel('total', 'frest');
			$output = $result->output($this, $format, $inline);
		}
		
		if (!$inline) {
			die();
		}
		
		return $output;
	}
	
	
	
	// ------------------------------------------
	// --   Helper   ----------------------------
	// ------------------------------------------

	/**
	 * @param string $resourceName
	 * @param Result\Error $error
	 * @return int
	 */
	public function resourceIDTypeFromResource($resourceName, &$error) {
		// figure out what type the ID is
		$resource = $this->loadResourceWithName($resourceName, NULL, $error);
		if (isset($error)) {
			return -1;
		}
		
		/** @var Setting\Field $fieldSetting */
		$resource->getIDField($fieldSetting);
		$resourceIDType = $fieldSetting->getVariableType();
		
		return $resourceIDType;
	}
	
	/**
	 * @param string $resourceName
	 * @param Request\Request $request
	 * @param Result\Error $error
	 *
	 * @return \FREST\Resource
	 */
	public function loadResourceWithName($resourceName, $request = NULL, &$error = NULL) {
		$resourceClassName = ucfirst($resourceName);
		$resourcePath = "{$this->config->getResourceDirectory()}/{$resourceClassName}.php";

		// verify resource existence
		if (!file_exists($resourcePath)) {
			$error = new Result\Error(Result\Error::Config, 500, "File for resource '{$resourceName}' not found at '{$resourcePath}'");
			return NULL;
		}

		// load the class, check if failed
		/** @noinspection PhpIncludeInspection */
		require_once $resourcePath;
		
		/*
		if (!@include_once($resourcePath)) {
			$error = new Result\Error(Result\Error::FailedLoadingResource, 500, "Failure in '{$resourcePath}'");
			return NULL;
		}
		*/
		
		if (!class_exists($resourceClassName, FALSE)) {
			$error = new Result\Error(Result\Error::Config, 500, "Class '{$resourceClassName}' not found in file '{$resourcePath}'");
			return NULL;
		}
		if (!is_subclass_of($resourceClassName, '\FREST\Resource')) {
			$error = new Result\Error(Result\Error::Config, 500, "Class '{$resourceClassName}' is not a subclass of \\FREST\\Resource");
			return NULL;
		}

		if (isset($this->loadedResources[$resourceClassName])) {
			/** @var \FREST\Resource $resource */
			$resource = $this->loadedResources[$resourceClassName];
		}
		else {
			/** @var \FREST\Resource $resource */
			$resource = new $resourceClassName($this);
			$resource->setDefaultLimit($this->config->getDefaultLimit());
			$resource->setMaxLimit($this->config->getMaxLimit());
			$resource->setAllowWildcards($this->config->getAllowWildcards());
			$resource->setAllowFieldsParameter($this->config->getAllowFieldsParameter());
			$resource->setAllowPartialSyntax($this->config->getAllowPartialSyntax());
			
			$this->loadedResources[$resourceClassName] = $resource;
		}

		if (isset($request) && method_exists($resource, 'isAuthRequiredForRequest')) {
			$scopes = NULL; // TODO: get scopes
			
			$isAuthRequired = $resource->isAuthRequiredForRequest($request, $scopes);

			if ($isAuthRequired) {
				if (isset($scopes)) {
					$scopeString = implode(' ', $scopes);
				}
				else {
					$scopeString = '';
				}
				
				$snoAuth = new \SNOAuth2_ClientCredentials(new \SNOAuth2Config($this->config->getAuthPDO()));
				$snoAuth->verifyResourceAccess($scopeString);
			}
		}
		
		return $resource;
	}

	/**
	 * @param string $label
	 * @param string $instance
	 */
	public function startTimingForLabel($label, $instance) {
		if (!isset($this->startTimes[$label])) {
			$this->startTimes[$label]['instances'] = array();
			$this->startTimes[$label]['time'] = 0;
		}
		
		if (count($this->startTimes[$label]['instances']) == 0) { // has no currently running timers for this label
			$this->startTimes[$label]['time'] = $this->getCurrentTime();
			//echo "start $label-$instance\n";
		}
		else {
			//echo "add $label-$instance\n";
		}

		$this->startTimes[$label]['instances'][$instance] = $instance;
	}

	/**
	 * @param string $label
	 * @param string $instance
	 */
	public function stopTimingForLabel($label, $instance) {
		if (!isset($this->timingObject)) {
			$this->timingObject = new \stdClass;
		}
		
		if (!isset($this->startTimes[$label]['instances'][$instance])) {
			//die("can't stop timing for '{$label}-{$instance}' because timing hasn't started with that label-instance yet");
		}
		//echo "stop $label-$instance\n";
		unset($this->startTimes[$label]['instances'][$instance]);
		
		if (!isset($this->startTimes[$label]['instances']) || count($this->startTimes[$label]['instances']) == 0) { // all timers for this label are done
			$startTime = $this->startTimes[$label]['time'];
			$timeDuration = $this->getCurrentTime() - $startTime;

			if (isset($this->timingObject->$label)) {
				$this->timingObject->$label += $timeDuration;
			}
			else {
				$this->timingObject->$label = $timeDuration;
			}
		}
	}

	/**
	 * @return float
	 */
	protected function getCurrentTime() {
		return microtime() * 1000;
	}
	

	/**
	 * @return \stdClass
	 */
	public function getTimingObject() {
		$timingObject = $this->timingObject;
		
		// format times
		foreach ($timingObject as $property=>$value) {
			$timingObject->$property = number_format($value, 3) . 'ms';
		}
		
		return $timingObject;
	}
	
	/**
	 * @return Config
	 */
	public function getConfig() {
		return $this->config;
	}

	/**
	 * @return Request\Request
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * @return Resource
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