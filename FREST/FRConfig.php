<?php
/**
 * Created by Brad Walker on 6/4/13 at 1:01 PM
*/

class FRConfig {

	/**
	 * Forces all http header status codes to be 200 (actual status is supplied within the response)
	 *
	 * @var bool (default: FALSE)
	 */
	protected $suppressHTTPStatusCodes = FALSE;
	
	/**
	 * Outputs timing and memory data alongside the response
	 *
	 * @var bool (default: FALSE)
	 */
	protected $showDiagnostics = FALSE;

	/**
	 * Runs a check against each resource as it is loaded to ensure
	 * its settings are valid.
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
	 * requests at random.
	 *
	 * @var bool (default: TRUE)
	 */
	protected $enableForcedMethod = TRUE;

	/**
	 * The directory in which all custom resources files are held.
	 *
	 * Files here are expected to be named the same as their
	 * associated resources with a capitalized first letter.
	 *
	 * Example:
	 *
	 *  Filename: Users.php
	 *  Class: Users extends resources
	 *  Resource: users
	 *
	 * @var string (default: dirname(__FILE__).'/resources')
	 */
	protected $resourceDirectory;

	/**
	 * A PDO object used to connect to the database holding the tables
	 * for the resources used in the API.
	 *
	 * @var PDO
	 */
	protected $pdo;

	/**
	 * A PDO object used to connect to the database for OAuth authentication.
	 * Uses the same PDO above unless otherwise specified.
	 *
	 * @var PDO
	 */
	protected $authPDO;


	/**
	 * @var array
	 */
	protected $configArray;

	
	
	/**
	 * @param PDO $pdo
	 */
	public function __construct($pdo = NULL) {
		$this->resourceDirectory = 'resources';
		$this->setPDO($pdo);
	}
	
	public static function withPDO($pdo) {
		return new FRConfig($pdo);
	}

	/**
	 * @param FREST $frest
	 * @param string $path
	 * @return FRConfig
	 * @throws Exception
	 */
	public static function fromFile($frest, $path = 'config.php') {
		$frest->startTimingForLabel(FRTiming::SETUP, 'config');
		
		if (!file_exists($path)) {
			throw new Exception("No config file at '{$path}'", 500);
		}
		
		include($path);
						
		if (!isset($config)) {
			throw new Exception("No config variable found in config file at '{$path}'", 500);
		}
		
		// PDO
		if (!isset($config['db'])) {
			throw new Exception("No db config settings specified in config file at '{$path}'", 500);
		}

		// create FRConfig
		$frestConfig = new FRConfig();
		$frestConfig->setConfigArray($config);

		$frest->stopTimingForLabel(FRTiming::SETUP, 'config');

		return $frestConfig;
	}

	private static function pdoFromConfigArray($configArray) {
		$dbType = $configArray['type'];
		$dbName = $configArray['name'];
		$dbHost = $configArray['host'];
		$dbUsername = $configArray['username'];
		$dbPassword = $configArray['password'];
		
		return new PDO("{$dbType}:dbname={$dbName};host={$dbHost}", $dbUsername, $dbPassword);
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
		}
		
		return $this->authPDO;
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
	 * @param \PDO $pdo
	 */
	public function setPDO($pdo) {
		$this->pdo = $pdo;
		
		if (!isset($this->authPDO)) {
			$this->authPDO = $pdo;
		}
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

		if (isset($configArray['suppressHTTPStatusCodes'])) {
			$this->setSuppressHTTPStatusCodes($configArray['suppressHTTPStatusCodes']);
		}

		if (isset($configArray['showDiagnostics'])) {
			$this->setShowDiagnostics($configArray['showDiagnostics']);
		}

		if (isset($configArray['checkResourceValidity'])) {
			$this->setCheckResourceValidity($configArray['checkResourceValidity']);
		}

		if (isset($configArray['enableForcedMethod'])) {
			$this->setEnableForcedMethod($configArray['enableForcedMethod']);
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