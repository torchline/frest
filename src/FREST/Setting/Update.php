<?php
/**
 * Created by Brad Walker on 6/5/13 at 1:12 PM
*/

namespace FREST\Setting;

/**
 * Class Update
 * @package FREST\Setting
 */
class Update {

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
	 * @param string $alias
	 * @param mixed $json
	 * @return Update|NULL
	 */
	public static function fromJSONAliasSetting($alias, $json) {
		$updateSetting = NULL;

		if (isset($json['access']['update'])) {
			$updateAccess = $json['access']['update'];

			$conditionFunction = isset($updateAccess['condition']) && strlen($updateAccess['condition']) > 0 ? $updateAccess['condition'] : NULL;
			$filterFunction = isset($updateAccess['filter']) && strlen($updateAccess['filter']) > 0 ? $updateAccess['filter'] : NULL;

			$updateSetting = new Update($alias, $conditionFunction, $filterFunction);
		}
		else {
			$updateSetting = new Update($alias, NULL, NULL);
		}
		
		return $updateSetting;
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