<?php
/**
 * Created by Brad Walker on 8/14/13 at 12:58 PM
*/

namespace FREST\Spec;

/**
 * Class Table
 * @package Router\Spec
 */
class Table {

	/** @var string */
	protected $table;
	
	/** @var string */
	protected $tableAbbreviation;

	/**
	 * @param string $table
	 * @param string $tableAbbreviation
	 */
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