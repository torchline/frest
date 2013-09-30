<?php
/**
 * Created by Brad Walker on 8/14/13 at 12:58 PM
*/

class FRTableSpec {

	/** @var string */
	protected $table;
	
	/** @var string */
	protected $tableAbbreviation;

	function __construct($table, $tableAbbreviation) {
		$this->table = $table;
		$this->tableAbbreviation = $tableAbbreviation;
	}

	/**
	 * @return string
	 */
	public function getTable()
	{
		return $this->table;
	}

	/**
	 * @return string
	 */
	public function getTableAbbreviation()
	{
		return $this->tableAbbreviation;
	}


}