<?php
/**
 * Created by Brad Walker on 8/14/13 at 12:58 PM
*/

namespace FREST\Spec;

/**
 * Class TableDelete
 * @package Router\Spec
 */
class TableDelete {

	/** @var string */
	protected $table;
	

	/**
	 * @param string $table
	 */
	function __construct($table) {
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