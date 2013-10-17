<?php
/**
 * Created by Brad Walker on 6/5/13 at 12:56 PM
*/

/**
 * Class FROrderSetting
 */
class FROrderSetting {

	/** @var string */
	protected $alias;

	/** @var bool */
	protected $ascendingEnabled = TRUE;

	/** @var bool */
	protected $descendingEnabled = TRUE;

	
	public function __construct($alias, $ascendingEnabled = TRUE, $descendingEnabled = TRUE) {
		$this->alias = $alias;
		$this->ascendingEnabled = $ascendingEnabled;
		$this->descendingEnabled = $descendingEnabled;
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
	public function getAscendingEnabled() {
		return $this->ascendingEnabled;
	}

	/**
	 * @return boolean
	 */
	public function getDescendingEnabled() {
		return $this->descendingEnabled;
	}
}