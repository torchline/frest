<?php
/**
 * Created by Brad Walker on 6/6/13 at 12:29 PM
*/

require_once(dirname(__FILE__).'/FRReadSetting.php');

/**
 * Class FRMultiResourceReadSetting
 */
class FRMultiResourceReadSetting extends FRReadSetting {

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
		$this->default = $default;
		
		$this->resourceName = $resourceName;
		$this->parameters = $parameters;

		$this->requiredAliases = array();
		
		// an alias should be surrounded by these two strings
		$leftDelimiter = '{';
		$rightDelimiter = '}';
		
		foreach ($parameters as $field=>$parameter) {
			$leftDelimPos = strpos($parameter, $leftDelimiter);
			$rightDelimPos = strpos($parameter, $rightDelimiter);
			
			if ($leftDelimPos !== FALSE && $rightDelimPos !== FALSE && $rightDelimPos > $leftDelimPos + 1) {
				$alias = substr($parameter, $leftDelimPos + 1, $rightDelimPos - ($leftDelimPos + 1));
				$this->requiredAliases[$field] = $alias;
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