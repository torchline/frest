<?php
/**
 * Created by Brad Walker on 6/5/13 at 12:47 PM
*/

/**
 * Class FRConditionSetting
 */
class FRConditionSetting {
	
	/** @var string */
	protected $alias;
	
	/** @var bool */
	protected $ascending = TRUE;
	
	/** @var bool */
	protected $descending = TRUE;


	/**
	 * @param string $alias
	 * @param bool $ascending
	 * @param bool $descending
	 */
	public function __construct($alias, $ascending = TRUE, $descending = TRUE) {
		$this->alias = $alias;
		$this->ascending = $ascending;
		$this->descending = $descending;
	}

	
	/**
	 * @return string
	 */
	public function getAlias() {
		return $this->alias;
	}

	/**
	 * @return boolean
	 */
	public function getAscending() {
		return $this->ascending;
	}

	/**
	 * @return boolean
	 */
	public function getDescending() {
		return $this->descending;
	}


	
}