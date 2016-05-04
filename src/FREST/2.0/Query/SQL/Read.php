<?php
/**
 * Created by Brad Walker on 5/3/16 at 22:39
*/

namespace FREST\Query\SQL;

class Read 
{
	/**
	 * 
	 * Example:
	 * [
	 *   tableName1: [
	 *     alias: t0,
	 *     select: [
	 *       column1: field1,
	 *       column2: field2,
	 *       column3: field3
	 *     ]
	 *   ]
	 * ]
	 * 
	 * @var array $fields
	 */
	protected $tables;

	/**
	 * @var int $limit
	 */
	protected $limit;

	/**
	 * @var int $offset
	 */
	protected $offset;
	
	
	public function __construct()
	{
		// no ambiguity in how the SQL string will look, this class defines all
		// TODO: figure out how to format tables/columns
		// order WHEREs alphabetically by default, support custom ordering in Computer file
		// order ORDERBYs as defined, pull outside of tables spec, figure out how to maintain references to tables (like full name or use alias)
	}
} 