<?php
/**
 * Created by Brad Walker on 6/5/13 at 2:02 PM
 */

namespace FREST\Result;

/**
 * Class Create
 * @package Router\Result
 */
class Create extends Result {

	/** @var \stdClass */
	protected $createdResourceID;


	/**
	 * @param int $createdResourceID
	 */
	public function __construct($createdResourceID) {
		$this->createdResourceID = $createdResourceID;
		$this->httpStatusCode = 201;
	}


	/**
	 * @return \stdClass
	 */
	protected function generateOutputObject() {
		$outputObject = new \stdClass;
		$outputObject->status = $this->httpStatusCode;
		
		$outputObject->response = new \stdClass;
		$outputObject->response->id = $this->createdResourceID;

		return $outputObject;
	}

	/**
	 * @return \stdClass
	 */
	public function getCreatedResourceID()
	{
		return $this->createdResourceID;
	}


	
}