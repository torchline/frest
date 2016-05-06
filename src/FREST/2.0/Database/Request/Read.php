<?php
/**
 * Created by Brad Walker on 5/3/16 at 22:39
*/

namespace FREST\Database\Request;

class Read
{
	/**
	 * An array of Spec\TableRead objects indicating which tables to read, columns/fields to read, and filters to apply
	 * 
	 * @var array $tableReadSpecs
	 */
	protected $tableReadSpecs;

	/**
	 * An array of Spec\OrderBy objects indicating how to order the results
	 * 
	 * @var array $orderBySpecs
	 */
	protected $orderBySpecs;
	
	/**
	 * How many results to return. Useful for pagination.
	 * 
	 * @var int $limit
	 */
	protected $limit;

	/**
	 * How far into the result set to begin returning results. Useful for pagination.
	 * 
	 * @var int $offset
	 */
	protected $offset;
	
	public function __construct($tableReadSpecs, $orderBySpecs = NULL, $limit = NULL, $offset = NULL)
	{
		// TODO: type-checking
		$this->tableReadSpecs = $tableReadSpecs;
		$this->orderBySpecs= $orderBySpecs;
		$this->limit = $limit;
		$this->offset = $offset;
	}
	
	/**
	 * @return int
	 */
	public function getLimit()
	{
		return $this->limit;
	}

	/**
	 * @return int
	 */
	public function getOffset()
	{
		return $this->offset;
	}

	/**
	 * @return array
	 */
	public function getOrderBySpecs()
	{
		return $this->orderBySpecs;
	}

	/**
	 * @return array
	 */
	public function getTableReadSpecs()
	{
		return $this->tableReadSpecs;
	}
} 