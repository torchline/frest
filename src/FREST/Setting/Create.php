<?php
/**
 * Created by Brad Walker on 6/5/13 at 1:00 PM
*/

namespace FREST\Setting;

/**
 * Class Create
 * @package FREST\Setting
 */
class Create {
	
	/** @var string */
	protected $alias;

	/** @var bool */
	protected $required = TRUE;
	
	/** @var string */
	protected $conditionFunction = NULL;
	
	/** @var string */
	protected $filterFunction = NULL;


	/**
	 * @param string $alias
	 * @param bool $required
	 * @param string|NULL $conditionFunction
	 * @param string|NULL $filterFunction
	 */
	public function __construct($alias, $required = TRUE, $conditionFunction = NULL, $filterFunction = NULL) {
		$this->alias = $alias;
		$this->required = $required;
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
	 * @return boolean
	 */
	public function getRequired() {
		return $this->required;
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