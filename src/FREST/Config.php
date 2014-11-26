<?php

namespace FREST;

/**
 * Created by Brad Walker on 6/4/13 at 1:01 PM
*/

class Config {

	/**
	 * Forces all http header status codes to be 200 (actual status is supplied within the response)
	 *
	 * @var bool (default: FALSE)
	 */
	protected $suppressHTTPStatusCodes = FALSE;

	/**
	 * Whether to allow the "*" fields parameters in read requests (retrieves all available fields from Resource)
	 *
	 * @var bool (default: FALSE)
	 */
	protected $allowWildcards = FALSE;

	/**
	 * Whether to allow partial syntax in read requests (e.g. /api/users?fields=id,name,company(name,size) )
	 *
	 * @var bool (default: TRUE)
	 */
	protected $allowPartialSyntax = TRUE;

	/**
	 * Whether to allow the 'fields' url parameter in read requests (e.g. /api/users?fields=id,name )
	 *
	 * @var bool (default: TRUE)
	 */
	protected $allowFieldsParameter = TRUE;
	
	/**
	 * Outputs timing and memory data alongside the response
	 *
	 * @var bool (default: FALSE)
	 */
	protected $showDiagnostics = FALSE;

	/**
	 * Runs a check against each resource as it is loaded to ensure
	 * its Setting are valid.
	 *
	 * @var bool (default: TRUE)
	 */
	protected $checkResourceValidity = TRUE;

	/**
	 * Enables use of the parameter 'method' to be used to manually
	 * specify which HTTP method to invoke. This is usually only
	 * necessary for clients that do not natively support the PUT
	 * and DELETE methods such as older browsers.
	 *
	 * For safety reasons, the value of this parameter is only ever
	 * used in a POST request. Enabling it via a GET request
	 * runs the risk of search engine spiders invoking data-altering
	 * Request at random.
	 *
	 * @var bool (default: TRUE)
	 */
	protected $enableForcedMethod = TRUE;

	/**
	 * Enables passing of parameters in the URL for POST, PUT, and DELETE methods.
	 * 
	 * @var bool (default: FALSE)
	 */
	protected $enableGETParametersInAllRequests = FALSE;
	
	/**
	 * The default LIMIT to apply to plural resource requests (can be overriden on a per-resource basis using the 'setDefaultLimit' method)
	 * 
	 * @var int (default: 10)
	 */
	protected $defaultLimit = 10;

	/**
	 * The max LIMIT available on plural resource requests (can be overriden on a per-resource basis using the 'setMaxLimit' method)
	 *
	 * @var int (default: 25)
	 */
	protected $maxLimit = 25;

	/**
	 * The directory in which all custom Resource files are held.
	 *
	 * Files here are expected to be named the same as their
	 * associated Resource with a capitalized first letter.
	 *
	 * Example:
	 *
	 *  Filename: Users.php
	 *  Class: Users extends Resource
	 *  Resource: users
	 *
	 * @var string (default: __DIR__.'/resources')
	 */
	protected $resourceDirectory;

	/**
	 * A PDO object used to connect to the database holding the tables
	 * for the Resource used in the API.
	 *
	 * @var \PDO
	 */
	protected $pdo;

	/**
	 * A PDO object used to connect to the database for OAuth authentication.
	 * Uses the same PDO above unless otherwise specified.
	 *
	 * @var \PDO
	 */
	protected $authPDO;


	/**
	 * @var array
	 */
	protected $configArray;

	/** @var array */
	protected static $configKeys = array(
		
	);
	
	
	/**
	 * @param \PDO $pdo
	 */
	public function __construct($pdo = NULL) {
		$this->resourceDirectory = 'resources';
		$this->setPDO($pdo);
	}
	
	/**
	 * @param \PDO $pdo
	 * @return Config
	 */
	public static function withPDO($pdo) {
		return new Config($pdo);
	}

	/**
	 * @param string $path
	 * @return Config
	 * @throws Exception
	 */
	public static function fromFile($path = 'frest-config.json') {		
		$searchPath = $path;
		$numDirsUp = 0;
		$fileExists = file_exists($searchPath);
		// search for config file starting in current directory, going up to a possible of 3 directories
		while (!$fileExists && $numDirsUp <= 3) {
			$searchPath = '../' . $searchPath;
			$fileExists = file_exists($searchPath);
			$numDirsUp++;
		}

		if (!$fileExists) {
			throw new Exception(Exception::Config, "No config file found with name '{$path}'");
		}
		
		$jsonConfigString = file_get_contents($searchPath);
		$config = json_decode($jsonConfigString, TRUE);
		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new Exception(Exception::Config, "Config file is not valid json");
		}
		
		// PDO
		if (!isset($config['db'])) {
			throw new Exception(Exception::Config, "No db config settings specified in frest config file at '{$path}'");
		}

		// create Config
		$frestConfig = new Config();
		$frestConfig->setConfigArray($config);

		return $frestConfig;
	}

	/**
	 * @param array $configArray
	 * @return \PDO
	 */
	private static function pdoFromConfigArray($configArray) {
		$dbType = isset($configArray['type']) ? $configArray['type'] : 'mysql';
		$dbName = $configArray['name'];
		$dbHost = isset($configArray['host']) ? $configArray['host'] : 'localhost';
		$dbUsername = isset($configArray['username']) ? $configArray['username'] : 'root';
		$dbPassword = isset($configArray['password']) ? $configArray['password'] : '';
		$dbCharset = isset($configArray['charset']) ? $configArray['charset'] : 'utf8';

		return new \PDO("{$dbType}:dbname={$dbName};host={$dbHost};charset={$dbCharset}", $dbUsername, $dbPassword);
	}
	
	
	/**
	 * @return string
	 */
	public function getResourceDirectory() {
		return $this->resourceDirectory;
	}

	/**
	 * @return boolean
	 */
	public function getCheckResourceValidity() {
		return $this->checkResourceValidity;
	}

	/**
	 * @return boolean
	 */
	public function getEnableForcedMethod() {
		return $this->enableForcedMethod;
	}

	/**
	 * @return \PDO
	 */
	public function getPDO() {
		if (!isset($this->pdo)) {
			if (isset($this->configArray['db'])) {
				$pdo = self::pdoFromConfigArray($this->configArray['db']);
				$this->setPDO($pdo);
			}
		}
		
		return $this->pdo;
	}

	/**
	 * @return \PDO
	 */
	public function getAuthPDO() {
		if (!isset($this->authPDO)) {
			if (isset($this->configArray['authDB'])) {
				$authPDO = self::pdoFromConfigArray($this->configArray['authDB']);
				$this->setAuthPDO($authPDO);
			}
			else if (isset($this->pdo)) {
				$this->setAuthPDO($this->pdo);
			}
		}
		
		return $this->authPDO;
	}

	/**
	 * @param int $defaultLimit
	 */
	public function setDefaultLimit($defaultLimit)
	{
		$this->defaultLimit = $defaultLimit;
	}

	/**
	 * @return int
	 */
	public function getDefaultLimit()
	{
		return $this->defaultLimit;
	}

	/**
	 * @param int $maxLimit
	 */
	public function setMaxLimit($maxLimit)
	{
		$this->maxLimit = $maxLimit;
	}

	/**
	 * @return int
	 */
	public function getMaxLimit()
	{
		return $this->maxLimit;
	}
	
	/**
	 * @return boolean
	 */
	public function getSuppressHTTPStatusCodes() {
		return $this->suppressHTTPStatusCodes;
	}

	/**
	 * @param boolean $suppressHTTPStatusCodes
	 */
	public function setSuppressHTTPStatusCodes($suppressHTTPStatusCodes)
	{
		$this->suppressHTTPStatusCodes = $suppressHTTPStatusCodes;
	}

	/**
	 * @param boolean $allowFieldsParameter
	 */
	public function setAllowFieldsParameter($allowFieldsParameter)
	{
		$this->allowFieldsParameter = $allowFieldsParameter;
	}

	/**
	 * @return boolean
	 */
	public function getAllowFieldsParameter()
	{
		return $this->allowFieldsParameter;
	}

	/**
	 * @param boolean $allowPartialSyntax
	 */
	public function setAllowPartialSyntax($allowPartialSyntax)
	{
		$this->allowPartialSyntax = $allowPartialSyntax;
	}

	/**
	 * @return boolean
	 */
	public function getAllowPartialSyntax()
	{
		return $this->allowPartialSyntax;
	}

	/**
	 * @param boolean $allowWildcards
	 */
	public function setAllowWildcards($allowWildcards)
	{
		$this->allowWildcards = $allowWildcards;
	}

	/**
	 * @return boolean
	 */
	public function getAllowWildcards()
	{
		return $this->allowWildcards;
	}
	
	

	/**
	 * @return boolean
	 */
	public function getShowDiagnostics() {
		return $this->showDiagnostics;
	}

	/**
	 * @param boolean $showDiagnostics
	 */
	public function setShowDiagnostics($showDiagnostics) {
		$this->showDiagnostics = $showDiagnostics;
	}
	
	/**
	 * @param string $resourceDirectory
	 */
	public function setResourceDirectory($resourceDirectory) {
		$this->resourceDirectory = $resourceDirectory;
	}

	/**
	 * @param boolean $checkResourceValidity
	 */
	public function setCheckResourceValidity($checkResourceValidity) {
		$this->checkResourceValidity = $checkResourceValidity;
	}

	/**
	 * @param boolean $enableForcedMethod
	 */
	public function setEnableForcedMethod($enableForcedMethod) {
		$this->enableForcedMethod = $enableForcedMethod;
	}

	/**
	 * @return boolean
	 */
	public function getEnableGETParametersInAllRequests()
	{
		return $this->enableGETParametersInAllRequests;
	}

	/**
	 * @param boolean $enableGETParametersInAllRequests
	 */
	public function setEnableGETParametersInAllRequests($enableGETParametersInAllRequests)
	{
		$this->enableGETParametersInAllRequests = $enableGETParametersInAllRequests;
	}

	/**
	 * @param \PDO $pdo
	 */
	public function setPDO($pdo) {
		$this->pdo = $pdo;
	}

	/**
	 * @param \PDO $authPDO
	 */
	public function setAuthPDO($authPDO) {
		$this->authPDO = $authPDO;
	}

	/**
	 * @param array $configArray
	 */
	public function setConfigArray($configArray) {
		$this->configArray = $configArray;
		
		if (isset($configArray['showDiagnostics'])) {
			$this->setShowDiagnostics($configArray['showDiagnostics']);
		}

		if (isset($configArray['allowWildcards'])) {
			$this->setAllowWildcards($configArray['allowWildcards']);
		}

		if (isset($configArray['allowFieldsParameter'])) {
			$this->setAllowFieldsParameter($configArray['allowFieldsParameter']);
		}
		
		if (isset($configArray['allowPartialSyntax'])) {
			$this->setAllowPartialSyntax($configArray['allowPartialSyntax']);
		}

		if (isset($configArray['suppressHTTPStatusCodes'])) {
			$this->setSuppressHTTPStatusCodes($configArray['suppressHTTPStatusCodes']);
		}
		
		if (isset($configArray['checkResourceValidity'])) {
			$this->setCheckResourceValidity($configArray['checkResourceValidity']);
		}

		if (isset($configArray['enableForcedMethod'])) {
			$this->setEnableForcedMethod($configArray['enableForcedMethod']);
		}

		if (isset($configArray['enableGETParametersInAllRequests'])) {
			$this->setEnableGETParametersInAllRequests($configArray['enableGETParametersInAllRequests']);
		}

		if (isset($configArray['defaultLimit'])) {
			$this->setDefaultLimit($configArray['defaultLimit']);
		}

		if (isset($configArray['maxLimit'])) {
			$this->setMaxLimit($configArray['maxLimit']);
		}
		
		if (isset($configArray['resourceDirectory'])) {
			$this->setResourceDirectory($configArray['resourceDirectory']);
		}
	}

	/**
	 * @return array
	 */
	public function getConfigArray()
	{
		return $this->configArray;
	}
	
	
	
	
}