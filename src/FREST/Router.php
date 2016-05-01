<?php

namespace FREST;

/** 
 * Class Router
 */ 
class Router {

	const FORCED_NULL = '__NULL__';
	
	/** @var \stdClass */
	protected $timingObject;
	
	/** @var array */
	protected $startTimes;
	
	/** @var Config */
	protected $config;

	/** @var Request\Request */
	protected $request;
	
	/** @var Resource */
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
	 * @param Config $config defaults to Config::fromFile('frest-config.php')
	 * @param string $resourceName The name of the resource for the request (defaults to base name of request url)
	 * @param int|string $resourceID The ID of the resource for the request (defaults to base name of request url if it is an int)
	 * @param array $parameters A list of key-value parameters to pass for the request (defaults to $_GET or $_POST) 
	 * @param int $requestMethod The method (get, post, put, delete) (e.g. FREST\Type\Method) of the request (defaults to REQUEST_METHOD)
	 * @param string $resourceFunctionName Custom Func to be invoked on resource
	 */
	public function __construct($config = NULL, $resourceName = NULL, $resourceID = NULL, $parameters = NULL, $requestMethod = NULL, $resourceFunctionName = NULL) {
		try {
			$this->startTimingForLabel(Type\Timing::TOTAL, 'frest');

			if (!isset($config)) {
				$config = Config::fromFile();

				if (!isset($config)) {
					throw new Exception(Exception::Config, "No config file or object supplied to Router");
				}
			}
			$this->config = $config;

			$this->suppressHTTPStatusCodes = $this->config->getSuppressHTTPStatusCodes();

			$this->startTimingForLabel(Type\Timing::SETUP, 'frest');

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

					if (is_numeric($urlBaseName) && strpos($urlBaseName, '.') == NULL) {
						// assume this int is actually an id and resource is specified in previous path component

						$resourceName = basename($urlInfo['dirname']);
						$resourceID = intval($urlBaseName);
					}
					else if (is_numeric($secondBaseName) && strpos($secondBaseName, '.') == NULL) {
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
				$resourceIDType = $this->resourceIDTypeFromResource($resourceName);

				$resourceID = Type\Variable::castValue($resourceID, $resourceIDType);
			}

			// determine request method
			if (!isset($requestMethod)) {
				$actualMethodString = $_SERVER['REQUEST_METHOD'];
				$actualMethod = Type\Method::fromString($actualMethodString);
			}
			else {
				$actualMethod = $requestMethod;
			}

			// check for forced method
			switch ($actualMethod) {
				case Type\Method::GET:
				case Type\Method::POST:
					if ($this->config->getEnableForcedMethod() && isset($_REQUEST['method'])) {
						$forcedMethodString = $_REQUEST['method'];
						$forcedMethod = Type\Method::fromString($forcedMethodString);

						// if method is valid
						if ($forcedMethod <= 0) {
							throw new Exception(Exception::InvalidMethod, "Method '{$forcedMethodString}");
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
					case Type\Method::GET:
						$parameters = $_GET;
						break;
					case Type\Method::POST:
					case Type\Method::PUT:
					case Type\Method::DELETE:
						$parameters = $this->config->getEnableGETParametersInAllRequests() ? $_REQUEST : $_POST;
						break;
					default:
						$methodString = Type\Method::getString($actualMethod);
						throw new Exception(Exception::InvalidMethod, "Method '{$methodString}");
						break;
				}
			}

			if (isset($parameters['suppress_http_status_codes'])) {
				$value = $parameters['suppress_http_status_codes'];
				$this->suppressHTTPStatusCodes = Type\Variable::castValue($value, Type\Variable::BOOL);
			}

			switch ($this->method) {
				case Type\Method::GET: // read
					if (isset($resourceID) && $resourceID != self::FORCED_NULL) {
						$this->request = new Request\SingularRead($this, $resourceID, $parameters, $resourceFunctionName);
					}
					else {
						$this->request = new Request\PluralRead($this, $parameters, $resourceFunctionName);
					}
					break;
				case Type\Method::POST: // create
					$this->request = new Request\Create($this, $resourceID, $parameters, $resourceFunctionName);
					break;
				case Type\Method::PUT: // update / create
					$this->request = new Request\Update($this, $resourceID, $parameters, $resourceFunctionName);
					break;
				case Type\Method::DELETE: // delete
					$this->request = new Request\Delete($this, $resourceID, $parameters, $resourceFunctionName);
					break;

				default:
					break;
			}

			$this->resource = $this->loadResourceWithName($resourceName, $this->request);

			$this->stopTimingForLabel(Type\Timing::SETUP, 'frest');
			$this->startTimingForLabel(Type\Timing::PROCESSING, 'frest');

			$this->request->setupWithResource($this->resource);

			$this->stopTimingForLabel(Type\Timing::PROCESSING, 'frest');
			$this->stopTimingForLabel(Type\Timing::TOTAL, 'frest');
		}
		catch (Exception $exception) {
			$this->error = $exception->generateError();
		}
	}

	/**
	 * @return Router
	 */
	public static function automatic() {
		return new Router();
	}

	/**
	 * @param string $resourceName
	 * @param string|int $resourceID
	 * @param array $parameters
	 * @param int $requestMethod
	 * @param string $resourceFunctionName
	 * @return Router
	 */
	public static function all($resourceName = NULL, $resourceID = NULL, $parameters = NULL, $requestMethod = NULL, $resourceFunctionName = NULL) {
		return new Router(NULL, $resourceName, $resourceID, $parameters, $requestMethod, $resourceFunctionName);
	}

	/**
	 * @param string|int $id
	 * @param string $resourceName
	 * @param array $parameters
	 * @param int $requestMethod
	 * @param string $resourceFunctionName
	 * @return Router
	 */
	public static function singular($id, $resourceName = NULL, $parameters = NULL, $requestMethod = NULL, $resourceFunctionName = NULL) {
		return new Router(NULL, $resourceName, $id, $parameters, $requestMethod, $resourceFunctionName);
	}

	/**
	 * @param string $resourceName
	 * @param array $parameters
	 * @param int $requestMethod
	 * @param string $resourceFunctionName
	 * @return Router
	 */
	public static function plural($resourceName = NULL, $parameters = NULL, $requestMethod = NULL, $resourceFunctionName = NULL) {
		return new Router(NULL, $resourceName, self::FORCED_NULL, $parameters, $requestMethod, $resourceFunctionName);
	}
	
		

	/**
	 * Outputs the result of the request
	 * 
	 * @param int $format
	 * @param bool $inline
	 * 
	 * @return mixed
	 */
	public function outputResult($format = Type\OutputFormat::JSON, $inline = FALSE) {
		if (isset($this->error)) {
			$result = $this->error;
		}
		else {
			$this->startTimingForLabel('total', 'frest');

			try {
				$result = $this->request->generateResult();
			}
			catch (Exception $exception) {
				$result = $exception->generateError();
			}
			
			$this->stopTimingForLabel('total', 'frest');
		}

		$output = $result->output($this, $format, $inline);

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
	 * 
	 * @return int
	 */
	public function resourceIDTypeFromResource($resourceName) {
		// figure out what type the ID is
		$resource = $this->loadResourceWithName($resourceName);
		
		/** @var Setting\Field $fieldSetting */
		$resource->getIDField($fieldSetting);
		$resourceIDType = $fieldSetting->getVariableType();
		
		return $resourceIDType;
	}
	
	/**
	 * @param string $resourceName
	 * @param Request\Request $request
	 *
	 * @return Resource
	 * @throws Exception
	 */
	public function loadResourceWithName($resourceName, $request = NULL) {
		$formattedResourceName = ucfirst($resourceName);
			
		if (!isset($this->loadedResources[$resourceName])) {
			$resourceDir = $this->config->getResourceDirectory();

			$jsonPath = "{$resourceDir}/{$formattedResourceName}.json";
			$classPath = "{$resourceDir}/{$formattedResourceName}.php";
			
			// verify resource existence
			if (file_exists($jsonPath)) {
				$jsonResource = new JSONResource($this, $resourceName, $jsonPath);
				$this->setupResource($jsonResource);
				
				$this->loadedResources[$resourceName] = $jsonResource;
			}
			else if (FALSE && file_exists($classPath)){ // TODO: deprecate PHP resource classes (should only be JSON + computers)
				// load the class, check if failed
				/** @noinspection PhpIncludeInspection */
				require_once $classPath;

				if (!class_exists($formattedResourceName, FALSE)) {
					throw new Exception(Exception::Config, "Class '{$formattedResourceName}' not found in file '{$classPath}'");
				}

				$frestResourceClassName = '\FREST\Resource';
				if (!is_subclass_of($formattedResourceName, $frestResourceClassName)) {
					throw new Exception(Exception::Config, "Class '{$formattedResourceName}' is not a subclass of {$frestResourceClassName}");
				}

				/** @var \FREST\Resource $resource */
				$classResource = new $formattedResourceName($this, $resourceName);
				$this->setupResource($classResource);
				
				$this->loadedResources[$resourceName] = $classResource;
			}
			else {
				throw new Exception(Exception::Config, "File for resource '{$resourceName}' not found in directory '{$resourceDir}'");
			}
		}

		/** @var Resource $resource */
		$resource = $this->loadedResources[$resourceName];
		
		return $resource;
	}

	/**
	 * @param \FREST\Resource $resource
	 */
	private function setupResource($resource) {
		$resource->setDefaultLimit($this->config->getDefaultLimit());
		$resource->setMaxLimit($this->config->getMaxLimit());
		$resource->setAllowWildcards($this->config->getAllowWildcards());
		$resource->setAllowFieldsParameter($this->config->getAllowFieldsParameter());
		$resource->setAllowPartialSyntax($this->config->getAllowPartialSyntax());
		
		$resource->setup(); // this is where Settings are created by custom class
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