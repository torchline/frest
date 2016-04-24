<?php
/**
 * Created by Brad Walker on 6/6/13 at 12:29 PM
*/

namespace FREST\Setting;

use FREST\Resource;

/**
 * Class PluralResourceRead
 * @package FREST\Setting
 */
class PluralResourceRead extends Read {
		
	/** @var array */
	protected $parameters;

	/** @var array */
	protected $requiredAliases;
	
	
	/**
	 * @param string $alias
	 * @param string $resourceName
	 * @param array $parameters
	 * @param bool $default
	 */
	public function __construct($alias, $resourceName, $parameters, $default = FALSE) {
		$this->alias = $alias;
		$this->resourceName = $resourceName;
		$this->parameters = $parameters;
		$this->default = $default;
		
		foreach ($parameters as $field=>$parameter) {
			$injectedAlias = Resource::aliasFromInjectedValue($parameter);

			if (isset($injectedAlias)) {
				$this->requiredAliases[$field] = $injectedAlias;
			}
		}
	}
	
	/**
	 * @return array
	 */
	public function getParameters() {
		return $this->parameters;
	}

	/**
	 * @return array
	 */
	public function getRequiredAliases() {
		return $this->requiredAliases;
	}

	
}