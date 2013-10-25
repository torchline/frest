<?php
/**
 * Created by Brad Walker on 6/4/13 at 4:16 PM
*/

namespace FREST\Result;

require_once(dirname(__FILE__) . "/Result.php");

/**
 * Class Error
 * @package FREST\Result
 */
class Error extends Result {

	/** @var int */
	protected $code;
	
	/** @var string */
	protected $developerMessage;
	
	/** @var string */
	protected $userMessage;

	/**
	 * @param int $code
	 * @param int $httpStatusCode
	 * @param string $developerMessage
	 * @param string| $userMessage
	 */
	function __construct($code, $httpStatusCode, $developerMessage, $userMessage = NULL) {
		$this->code = $code;
		$this->httpStatusCode = $httpStatusCode;
		$this->developerMessage = $developerMessage;
		$this->userMessage = $userMessage;
	}

	/**
	 * @return \stdClass
	 */
	protected function generateOutputObject() {
		$outputObject = new \stdClass();
		$outputObject->code = $this->code;
		$outputObject->status = $this->httpStatusCode;
		$outputObject->developerMessage = $this->getDeveloperMessage();
		
		if (isset($this->userMessage)) { // TODO: define local default user messages
			$outputObject->userMessage = $this->userMessage;
		}

		return $outputObject;
	}
	

	/**
	 * @return int
	 */
	public function getCode() {
		return $this->code;
	}

	/**
	 * @return string
	 */
	public function getDeveloperMessage() {
		return $this->developerMessage;
	}

	/**
	 * @return string
	 */
	public function getUserMessage() {
		return $this->userMessage;
	}
	
}