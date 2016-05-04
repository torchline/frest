<?php
/**
 * Created by Brad Walker on 5/3/16 at 18:02
*/

namespace FREST\Request;

class Read extends Request
{
	/**
	 * @var string $resourceName
	 */
	protected $resourceName;
	
	/**
	 * Which fields to read back. Supports recursive subspecification.
	 * 
	 * Example:
	 * [ 
	 *   id: 1, 
	 *   name: 1, 
	 *   category: [
	 *     name: 1
	 *     subcategory: [
	 *       name: 1
	 *     ]
	 *   ] 
	 * ]
	 * 
	 * @var array $fields
	 */
	protected $fields;

	/**
	 * 
	 * Example:
	 * [
	 *   name: ["=", "basketball"],
	 *   category.subcategory.name: ["like", "spor~"]
	 * ]
	 * 
	 * @var array $filters
	 */
	protected $filters;

	/**
	 * Which fields and directions to use to sort the results. Supports recursive subspecification.
	 * 
	 * Example:
	 * [
	 *   category.subcategory.name: desc,
	 *   name: asc
	 * ]
	 * 
	 * @var array $orderBy
	 */
	protected $orderBy;

	/**
	 * @var int $limit
	 */
	protected $limit;

	/**
	 * @var int $offset
	 */
	protected $offset;

	/**
	 * @param string $resourceName
	 * @param array $fields
	 * @param array $filters
	 * @param array $orderBy
	 * @param int $limit
	 * @param int $offset
	 */
	public function __construct($resourceName, $fields, $filters = NULL, $orderBy = NULL, $limit = NULL, $offset = NULL)
	{
		// TODO: type checking
		$this->resourceName = $resourceName;
		$this->fields = $fields;
		$this->filters = $filters;
		$this->orderBy = $orderBy;
		$this->limit = $limit;
		$this->offset = $offset;
	}

	/**
	 * @return array
	 */
	public function getFields()
	{
		return $this->fields;
	}

	/**
	 * @return array
	 */
	public function getFilters()
	{
		return $this->filters;
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
	public function getOrderBy()
	{
		return $this->orderBy;
	}
} 