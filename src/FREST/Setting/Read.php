<?php
/**
 * Created by Brad Walker on 6/5/13 at 3:14 PM
*/

namespace FREST\Setting;
use FREST\Exception;

/**
 * Class Read
 * @package FREST\Setting
 */
abstract class Read {

	/** @var string */
	protected $resourceName;
	
	/** @var string */
	protected $alias;

	/** @var bool */
	protected $default = TRUE;


	/**
	 * @param string $alias
	 * @param mixed $setting
	 * @return Read|NULL
	 * @throws \FREST\Exception
	 */
	public static function fromJSONAliasSetting($alias, $setting) {
		$readAccess = NULL;
		if (isset($setting['access']['read'])) {
			$readAccess = $setting['access']['read'];
		}
		else {
			$readAccess = array();
		}

		$readSetting = NULL;
		if (isset($setting['resource'])) { // one-to-one
			if (isset($setting['resource']['name']) && isset($setting['resource']['reference'])) {
				$default = isset($readAccess['default']) ? (bool)$readAccess['default'] : FALSE;
				$aliasesToRead = isset($setting['resource']['fields']) ? $setting['resource']['fields'] : NULL;
				
				$readSetting = new SingularResourceRead($alias, $setting['resource']['name'], $setting['resource']['reference'], $aliasesToRead, $default);
			}
			else {
				throw new Exception(Exception::Config, "Invalid 'resource' object in '{$alias}'. Must specifiy 'name' and 'reference' (optional: 'fields').");
			}
		}
		else if (isset($setting['resources'])) { // many-to-one
			if (isset($setting['resources']['parameters'])) {
				if (is_array($setting['resources']['parameters']) && count($setting['resources']['parameters']) > 0) {
					$default = isset($readAccess['default']) ? (bool)$readAccess['default'] : FALSE;

					$readSetting = new PluralResourceRead($alias, $setting['resources']['name'], $setting['resources']['parameters'], $default);
				}
				else {
					throw new Exception(Exception::Config, "Invalid 'many.parameters' object in '{$alias}'. Must have count > 0.");
				}
			}
			else {
				throw new Exception(Exception::Config, "Invalid 'resources' object in '{$alias}'. Must specifiy 'name' and 'parameters'.");
			}
		}
		else if (isset($setting['function'])) {
			// TODO: more safety checking for function requiredKeys
			$requiredKeys = NULL;
			if (is_array($setting['requiredKeys']) && count($setting['requiredKeys']) > 0) {
				$requiredKeys = $setting['requiredKeys'];
			}
			
			$functionName = $setting['function'];
			$default = isset($readAccess['default']) ? (bool)$readAccess['default'] : FALSE;
			$readSetting = new ComputedRead($alias, $functionName, $requiredKeys, $default);
		}
		else { // field
			$default = isset($readAccess['default']) ? (bool)$readAccess['default'] : TRUE;
			$filterFunction = isset($readAccess['filter']) && strlen($readAccess['filter']) > 0 ? $readAccess['filter'] : NULL;

			$readSetting = new FieldRead($alias, $filterFunction, $default);
		}
		
		return $readSetting;
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
	public function getResourceName() {
		return $this->resourceName;
	}

	/**
	 * @return bool
	 */
	public function getDefault() {
		return $this->default;
	}
}