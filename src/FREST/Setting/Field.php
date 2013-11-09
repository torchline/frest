<?php
/**
 * Created by Brad Walker on 6/5/13 at 12:02 PM
*/

namespace FREST\Setting;

use FREST\Type;

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