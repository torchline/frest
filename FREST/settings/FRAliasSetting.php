<?php
/**
 * Created by Brad Walker on 6/5/13 at 12:15 PM
*/

/**
 * Class FRAliasSetting
 */
class FRAliasSetting {

	/** @var string */
	protected $alias;
	
	/** @var string */
	protected $field;


	/**
	 * @param string $alias
	 * @param string $field
	 */
	public function __construct($alias, $field) {
		$this->alias = $alias;
		$this->field = $field;
	}


	/**
	 * @return string
	 */
	public function getAlias() {
		return $this->alias;
	}

	/**
	 * @return string
	 */
	public function getField() {
		return $this->field;
	}
	
	
}