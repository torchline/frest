<?php
/**
 * Created by Brad Walker on 6/5/13 at 2:11 PM
*/

namespace FREST\Result;

require_once(dirname(__FILE__) . '/Result.php');

/**
 * Class PluralRead
 * @package FREST\Result
 */
class PluralRead extends Result {

	/** @var int */
	protected $limit;

	/** @var int */
	protected $offset;

	/** @var int */
	protected $count;

	/** @var array */
	protected $resourceObjects;
	
	

	/**
	 * @param array $objects
	 * @param int $limit
	 * @param int $offset
	 * @param int $count
	 */
	public function __construct($objects, $limit, $offset, $count) {
		$this->httpStatusCode = 200;
		
		$this->resourceObjects = $objects;
		$this->limit = $limit;
		$this->offset = $offset;
		$this->count = $count;
	}

	/**
	 * @return \stdClass
	 */
	protected function generateOutputObject() {
		$outputObject = new \stdClass();
		$outputObject->status = $this->httpStatusCode;
		$outputObject->response = $this->resourceObjects;

		$outputObject->meta = new \stdClass();
		$outputObject->meta->limit = $this->limit;
		$outputObject->meta->offset = $this->offset;
		$outputObject->meta->count = $this->count;

		return $outputObject;
	}
	
	
	
	/**
	 * @return int
	 */
	public function getLimit() {
		return $this->limit;
	}

	/**
	 * @return int
	 */
	public function getOffset() {
		return $this->offset;
	}

	/**
	 * @return int
	 */
	public function getCount() {
		return $this->count;
	}	

	/**
	 * @return array
	 */
	public function getResourceObjects() {
		return $this->resourceObjects;
	}

	
}