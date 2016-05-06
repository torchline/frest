<?php
/**
 * Created by Brad Walker on 5/5/16 at 11:54 AM
*/

namespace FREST\Database\Request\Spec;

class OrderBy 
{
	/**
	 * @var string $table
	 */
	protected $table;

	/**
	 * @var string $column
	 */
	protected $column;

	/**
	 * @var string $direction
	 */
	protected $direction;

	/**
	 * @param string $table
	 * @param string $column
	 * @param string $direction
	 */
	function __construct($table, $column, $direction)
	{
		$this->table = $table;
		$this->column = $column;
		$this->direction = $direction;
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
	public function getColumn()
	{
		return $this->column;
	}

	/**
	 * @return string
	 */
	public function getDirection()
	{
		return $this->direction;
	}
} 