<?php
/**
 * Created by Brad Walker on 6/6/13 at 12:29 PM
*/

require_once(dirname(__FILE__).'/FRReadSetting.php');

/**
 * Class FRPluralResourceReadSetting
 */
class FRPluralResourceReadSetting extends FRReadSetting {

	/** @var string */
	protected $resourceName;
	
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
			$injectedAlias = FRResource::aliasFromInjectedValue($parameter);

			if (isset($injectedAlias)) {
				$this->requiredAliases[$field] = $injectedAlias;
			}
		}
	}
	
	

	/**
	 * @return string
	 */
	public function getResourceName() {
		return $this->resourceName;
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