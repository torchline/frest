<?php
/**
 * Created by Brad Walker on 6/4/13 at 1:01 PM
*/

class FRConfig {
	
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
	 * @var string
	 */
	protected $controllerDirectory;

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
	 * @param PDO $pdo
	 */
	public function __construct($pdo) {
		$this->resourceDirectory = 'resources';
		$this->setPDO($pdo);
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
		return $this->pdo;
	}

	/**
	 * @return \PDO
	 */
	public function getAuthPDO() {
		return $this->authPDO;
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
	
	
	
	
}