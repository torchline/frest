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
	 * @param string $alias
	 * @param mixed $setting
	 * @return Create|NULL
	 */
	public static function fromJSONAliasSetting($alias, $setting) {
		$createSetting = NULL;

		if (isset($setting['access']['create'])) {
			$createAccess = $setting['access']['create'];

			$required = isset($readAccess['required']) ? (bool)$readAccess['required'] : TRUE;
			$conditionFunction = isset($createAccess['condition']) && strlen($createAccess['condition']) > 0 ? $createAccess['condition'] : NULL;
			$filterFunction = strlen($createAccess['filter']) > 0 ? $createAccess['filter'] : NULL;

			$createSetting = new Create($alias, $required, $conditionFunction, $filterFunction);
		}
		else {
			$createSetting = new Create($alias, FALSE, NULL, NULL);
		}

		return $createSetting;
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