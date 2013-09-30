<?php
/**
 * Created by Brad Walker on 6/5/13 at 2:02 PM
 */

require_once(dirname(__FILE__).'/FRResult.php');

class FRCreateResult extends FRResult {

	/** @var stdClass */
	protected $createdResourceID;


	/**
	 * @param int $createdResourceID
	 */
	public function __construct($createdResourceID) {
		$this->createdResourceID = $createdResourceID;
		$this->httpStatusCode = 201;
	}



	protected function generateOutputObject() {
		$outputObject = new stdClass();
		$outputObject->status = $this->httpStatusCode;
		$outputObject->id = $this->createdResourceID;

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