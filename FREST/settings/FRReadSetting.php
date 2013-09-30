<?php
/**
 * Created by Brad Walker on 6/5/13 at 3:14 PM
*/

abstract class FRReadSetting {
	
	/** @var string */
	protected $alias;

	/** @var bool */
	protected $default = TRUE;
	

	/**
	 * @return string
	 */
	public function getAlias() {
		return $this->alias;
	}

	/**
	 * @return bool
	 */
	public function getDefault() {
		return $this->default;
	}
}