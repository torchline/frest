<?php
/**
 * Created by Brad Walker on 6/5/13 at 2:23 PM
 */

namespace FREST\Spec;

/**
 * Class Order
 * @package FREST\Spec
 */
class Order {

	/** @var string */
	protected $alias;

	/** @var $field */
	protected $field;
	
	/** @var string */
	protected $direction;

	/** @var $table */
	protected $table;

	/**
	 * @param string $alias
	 * @param string $field
	 * @param string $table
	 * @param string $direction
	 */
	function __construct($alias, $field, $table, $direction) {
		$this->alias = $alias;
		$this->field = $field;
		$this->table = $table;
		$this->direction = $direction;
	}


	/**
	 * @return string
	 */
	public function getAlias()
	{
		return $this->alias;
	}

	/**
	 * @return mixed
	 */
	public function getField()
	{
		return $this->field;
	}
	
	/**
	 * @return string
	 */
	public function getDirection()
	{
		return $this->direction;
	}

	/**
	 * @return mixed
	 */
	public function getTable()
	{
		return $this->table;
	}


}