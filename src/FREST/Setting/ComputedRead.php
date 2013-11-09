<?php
/**
 * Created by Brad Walker on 6/5/13 at 3:06 PM
*/

namespace FREST\Setting;

/**
 * Class ComputedRead
 * @package FREST\Spec
 */
class ComputedRead extends Read {

	/** @var string */
	protected $function;
	
	/** @var array */
	protected $requiredAliases = NULL;
	
	
	/**
	 * @param string $alias
	 * @param string $function
	 * @param array $requiredAliases
	 * @param bool $default
	 */
	public function __construct($alias, $function, $requiredAliases = NULL, $default = FALSE) {
		$this->alias = $alias;
		$this->default = $default;
		
		$this->function = $function;

		$keyedRequiredAliases = array();
		foreach ($requiredAliases as $requiredAlias) {
			$keyedRequiredAliases[$requiredAlias] = $requiredAlias;
		}
		$this->requiredAliases = $keyedRequiredAliases;
	}
	
	

	/**
	 * @return string
	 */
	public function getFunction() {
		return $this->function;
	}

	/**
	 * @return array
	 */
	public function getRequiredAliases() {
		return $this->requiredAliases;
	}
	
	
}