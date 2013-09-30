<?php
/**
 * Created by Brad Walker on 8/14/13 at 12:58 PM
*/

class FRTableDeleteSpec {

	/** @var string */
	protected $table;
	

	/**
	 * @param string $table
	 * @param FRErrorResult $error
	 */
	function __construct($table, &$error = NULL) {
		$this->table = $table;
	}
	
	
	/**
	 * @return string
	 */
	public function getTable()
	{
		return $this->table;
	}
	
}