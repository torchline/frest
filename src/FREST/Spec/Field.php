<?php
/**
 * Created by Brad Walker on 6/5/13 at 4:29 PM
*/

namespace FREST\Spec;

/**
 * Class Field
 * @package FREST\Spec
 */
class Field {

	/** @var string */
	protected $table;
	
	/** @var string */
	protected $tableAbbreviation;
	
	/** @var string */
	protected $field;
	
	/** @var string */
	protected $name;


	/**
	 * @param string $table
	 * @param string $tableAbbreviation
	 * @param string $field
	 * @param string $name
	 */
	public function __construct($table, $tableAbbreviation, $field, $name) {
		$this->table = $table;
		$this->tableAbbreviation = $tableAbbreviation;
		$this->field = $field;
		$this->name = $name;
	}
	
	

	/**
	 * @return string
	 */
	public function getTable() {
		return $this->table;
	}

	/**
	 * @return string
	 */
	public function getTableAbbreviation()
	{
		return $this->tableAbbreviation;
	}

	/**
	 * @return string
	 */
	public function getField() {
		return $this->field;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}


	
	
}