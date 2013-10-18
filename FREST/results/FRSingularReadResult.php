<?php
/**
 * Created by Brad Walker on 6/5/13 at 2:02 PM
*/

require_once(dirname(__FILE__).'/FRResult.php');

class FRSingularReadResult extends FRResult {
	
	/** @var stdClass */
	protected $resourceObject;
	
	
	/**
	 * @param stdClass $object
	 */
	public function __construct($object) {
		$this->httpStatusCode = 200;
		$this->resourceObject = $object;
	}


	
	protected function generateOutputObject() {
		$outputObject = new stdClass();
		$outputObject->status = $this->httpStatusCode;
		$outputObject->response = $this->resourceObject;

		return $outputObject;
	}	
	

	/**
	 * @return \stdClass
	 */
	public function getResourceObject() {
		return $this->resourceObject;
	}
}