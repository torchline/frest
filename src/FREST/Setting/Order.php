<?php
/**
 * Created by Brad Walker on 6/5/13 at 12:56 PM
*/

namespace FREST\Setting;

/**
 * Class Order
 * @package Router\Setting
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