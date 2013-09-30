<?php
/**
 * Created by Brad Walker on 6/5/13 at 11:43 AM
*/

require_once(dirname(__FILE__).'/FRReadSetting.php');

/**
 * Class FRFieldReadSetting
 */
class FRFieldReadSetting extends FRReadSetting {

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