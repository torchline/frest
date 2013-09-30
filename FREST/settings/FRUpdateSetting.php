<?php
/**
 * Created by Brad Walker on 6/5/13 at 1:12 PM
*/

/**
 * Class FRUpdateSetting
 */
class FRUpdateSetting {

	/** @var string */
	protected $alias;

	/** @var string */
	protected $conditionFunction = NULL;

	/** @var string */
	protected $filterFunction = NULL;

	
	/**
	 * @param string $alias
	 * @param string|NULL $conditionFunction
	 * @param string|NULL $filterFunction
	 */
	public function __construct($alias, $conditionFunction = NULL, $filterFunction = NULL) {
		$this->alias = $alias;
		$this->conditionFunction = $conditionFunction;
		$this->filterFunction = $filterFunction;
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
	public function getConditionFunction() {
		return $this->conditionFunction;
	}

	/**
	 * @return string
	 */
	public function getFilterFunction() {
		return $this->filterFunction;
	}


}