<?php
/**
 * Created by Brad Walker on 6/5/13 at 11:43 AM
*/

namespace FREST\Setting;

/**
 * Class FieldRead
 * @package Router\Setting
 */
class FieldRead extends Read {

	/** @var string */
	protected $filterFunction;
	
	/**
	 * @param string $alias
	 * @param string $filterFunction
	 * @param bool $default
	 */
	public function __construct($alias, $filterFunction = NULL, $default = TRUE) {
		$this->alias = $alias;
		$this->filterFunction = $filterFunction;
		$this->default = $default;
	}

	/**
	 * @return string
	 */
	public function getFilterFunction()
	{
		return $this->filterFunction;
	}
	
	
	
}