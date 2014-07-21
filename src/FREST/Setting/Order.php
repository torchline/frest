<?php
/**
 * Created by Brad Walker on 6/5/13 at 12:56 PM
*/

namespace FREST\Setting;

/**
 * Class Order
 * @package FREST\Setting
 */
class Order {

	/** @var string */
	protected $alias;

	/** @var bool */
	protected $ascendingEnabled = TRUE;

	/** @var bool */
	protected $descendingEnabled = TRUE;


	/**
	 * @param string $alias
	 * @param bool $ascendingEnabled
	 * @param bool $descendingEnabled
	 */
	public function __construct($alias, $ascendingEnabled = TRUE, $descendingEnabled = TRUE) {
		$this->alias = $alias;
		$this->ascendingEnabled = $ascendingEnabled;
		$this->descendingEnabled = $descendingEnabled;
	}

	/**
	 * @param string $alias
	 * @param mixed $setting
	 * @return Condition|NULL
	 */
	public static function fromJSONAliasSetting($alias, $setting) {
		$orderSetting = NULL;

		if (isset($setting['access']['read']['condition'])) {
			$orderAccess = $setting['access']['read']['condition'];

			$ascending = isset($orderAccess['ascending']) ? (bool)$orderAccess['ascending'] : TRUE;
			$descending = isset($orderAccess['descending']) ? (bool)$orderAccess['descending'] : TRUE;

			$orderSetting = new Order($alias, $ascending, $descending);
		}
		else {
			$orderSetting = new Order($alias, TRUE, TRUE);
		}

		return $orderSetting;
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