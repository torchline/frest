<?php
/**
 * Created by Brad Walker on 6/5/13 at 12:47 PM
*/

namespace FREST\Setting;

/**
 * Class Condition
 * @package FREST\Setting
 */
class Condition {
	
	/** @var string */
	protected $alias;
	
	
	/**
	 * @param string $alias
	 */
	public function __construct($alias) {
		$this->alias = $alias;
	}
	// TODO: add parameters on to Condition Setting

	/**
	 * @param string $alias
	 * @param mixed $setting
	 * @return Condition|NULL
	 */
	public static function fromJSONAliasSetting($alias, $setting) {
		$conditionSetting = NULL;

		if (isset($setting['access']['read']['condition'])) {
			$conditionAccess = $setting['access']['read']['condition'];

			$conditionSetting = new Condition($alias);
		}
		else {
			$conditionSetting = new Condition($alias);
		}
		
		return $conditionSetting;
	}
	
	/**
	 * @return string
	 */
	public function getAlias() {
		return $this->alias;
	}
}