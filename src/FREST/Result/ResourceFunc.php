<?php
/**
 * Created by Brad Walker on 8/16/13 at 10:21 PM
*/

namespace FREST\Result;

/**
 * Class ResourceFunc
 * @package FREST\Result
 */
class ResourceFunc extends Result {

	/** @var \stdClass */
	protected $object;


	/**
	 * @param int $httpStatusCode
	 * @param array|\stdClass $object
	 */
	public function __construct($httpStatusCode, $object) {
		$this->httpStatusCode = $httpStatusCode;
		$this->object = $object;
	}


	/**
	 * @return \stdClass
	 */
	protected function generateOutputObject() {
		$outputObject = new \stdClass();
		$outputObject->status = $this->httpStatusCode;
		$outputObject->response = $this->object;
		
		return $outputObject;
	}

	/**
	 * @return \stdClass
	 */
	public function getObject()
	{
		return $this->object;
	}



}