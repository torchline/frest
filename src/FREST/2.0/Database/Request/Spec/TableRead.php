<?php
/**
 * Created by Brad Walker on 5/5/16 at 11:53 AM
*/

namespace FREST\Database\Request\Spec;

class TableRead 
{
	/**
	 * The table name in the database that will be read from.
	 * 
	 * @var string $table
	 */
	protected $table;

	/**
	 * The field name that this table/resource. Helpful when the source resource has multiple children resources that belong to the same table. This should be NULL if this is the source resource.
	 * 
	 * Example:
	 * // TODO: show example where $fieldOnSourceResource is relevant
	 * 
	 * @var string $fieldOnSourceResource
	 */
	protected $fieldOnSourceResource;

	/**
	 * An array keyed by the list of columns to read from the table. The values of the array are the fields on the resource that these columns map to.
	 * 
	 * Example:
	 * [
	 *   UserID: id,
	 *   Name: name,
	 *   Email: email
	 * ]
	 * 
	 * @var array
	 */
	protected $fieldsByColumn;

	/**
	 * An array keyed by a list of columns to filter by. The values of the array contain the comparator and value to filter by.
	 * 
	 * Example:
	 * [
	 *   UserID: '>= 5304',
	 *   Name: 'LIKE "Jess%"'
	 * ]
	 * 
	 * @var array
	 */
	protected $filtersByColumn;

	function __construct($table, $fieldsByColumn, $filtersByColumn = NULL, $fieldOnSourceResource = NULL)
	{
		$this->table = $table;
		$this->fieldsByColumn = $fieldsByColumn;
		$this->filtersByColumn = $filtersByColumn;
		$this->fieldOnSourceResource = $fieldOnSourceResource;
	}

	/**
	 * @return string
	 */
	public function getFieldOnSourceResource()
	{
		return $this->fieldOnSourceResource;
	}

	/**
	 * @return array
	 */
	public function getFieldsByColumn()
	{
		return $this->fieldsByColumn;
	}

	/**
	 * @return array
	 */
	public function getFiltersByColumn()
	{
		return $this->filtersByColumn;
	}

	/**
	 * @return string
	 */
	public function getTable()
	{
		return $this->table;
	}
} 