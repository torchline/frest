<?php
/**
 * Created by Brad Walker on 6/5/13 at 12:02 PM
*/

namespace FREST\Setting;

use FREST\Type;
use FREST\Exception;

/**
 * Class Field
 * @package FREST\Setting
 */
class Field {

	/** @var string */
	protected $alias;
	
	/** @var string */
	protected $field;
	
	/** @var int */
	protected $variableType;


	/**
	 * @param string $alias
	 * @param string $field
	 * @param int $variableType
	 */
	public function __construct($alias, $field, $variableType = Type\Variable::STRING) {
		$this->alias = $alias;
		$this->field = $field;
		$this->variableType = $variableType;
	}

	/**
	 * @param string $alias
	 * @param mixed $setting
	 * @return Field|NULL
	 * @throws Exception
	 */
	public static function fromJSONAliasSetting($alias, $setting) {
		$fieldSetting = NULL;

		if (!isset($setting['resources'])) { // not many-to-one
			$field = isset($setting['field']) ? $setting['field'] : $alias;
			$type = isset($setting['type']) ? Type\Variable::typeFromString($setting['type']) : Type\Variable::STRING;
			if (!isset($type)) {
				throw new Exception(Exception::Config, "Type '{$setting['type']}' is invalid for alias '{$alias}'");
			}

			$fieldSetting = Settings::field($alias, $field, $type);
		}

		return $fieldSetting;
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

	/**
	 * @return int
	 */
	public function getVariableType() {
		return $this->variableType;
	}
	
	
}