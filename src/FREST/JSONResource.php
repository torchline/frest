<?php
/**
 * Created by Brad Walker on 7/20/14 at 1:33 PM
*/

namespace FREST;

/**
 * Class JSONResource
 * @package FREST
 */
class JSONResource extends Resource {

	/**
	 * @var string
	 */
	protected $filePath;
	
	/**
	 * @param Router $router
	 * @param string $filePath
	 */
	public function __construct($router, $filePath) {
		$this->filePath = $filePath;
		parent::__construct($router);
	}
	
	public function setup() {
		$this->loadFromFilePath($this->filePath);
	}

	/**
	 * @param string $filePath
	 * @throws Exception
	 */
	protected function loadFromFilePath($filePath) {
		$jsonString = preg_replace('/\s+/', '', file_get_contents($filePath));
		$json = json_decode($jsonString, TRUE);
		
		if (!isset($json)) {
			throw new Exception(Exception::Config, "Invalid JSON for resource at file path '{$filePath}'");
		}
		
		if (isset($json)) {
			if (!isset($json['table'])) {
				$json['table'] = basename($filePath, '.json');
			}
		}		
		
		$this->loadFromJSON($json);
	}

	/**
	 * @param mixed $json
	 * @throws Exception
	 */
	protected function loadFromJSON($json) {
		if (!isset($json['table'])) {
			throw new Exception(Exception::Config, "No table declared in JSON");
		}
		if (!isset($json['resource'])) {
			throw new Exception(Exception::Config, "No resource declared in JSON");
		}
		
		$table = $json['table'];
		$resource = $json['resource'];
		
		$fieldSettings = array();
		$readSettings = array();
		$createSettings = array();
		$updateSettings = array();
		$conditionSettings = array();
		$orderSettings = array();

		foreach ($resource as $alias=>$setting) {
			$fieldSetting = Setting\Field::fromJSONAliasSetting($alias, $setting);
			if ($fieldSetting) {
				$fieldSettings[] = $fieldSetting;
			}
			
			$readSetting = Setting\Read::fromJSONAliasSetting($alias, $setting);
			if (isset($readSetting)) {
				$readSettings[] = $readSetting;
			}

			$createSetting = Setting\Create::fromJSONAliasSetting($alias, $setting);
			if (isset($createSetting)) {
				$createSettings[] = $createSetting;
			}

			$updateSetting = Setting\Update::fromJSONAliasSetting($alias, $setting);
			if (isset($updateSetting)) {
				$updateSettings[] = $updateSetting;
			}

			$conditionSetting = Setting\Condition::fromJSONAliasSetting($alias, $setting);
			if (isset($conditionSetting)) {
				$conditionSettings[] = $conditionSetting;
			}

			$orderSetting = Setting\Order::fromJSONAliasSetting($alias, $setting);
			if (isset($orderSetting)) {
				$orderSettings[] = $orderSetting;
			}
		}
		
		// set all settings that were read
		$this->setTableSettings(array(
			new Setting\Table($table, $fieldSettings)
		));
		$this->setReadSettings($readSettings);
		$this->setCreateSettings($createSettings);
		$this->setUpdateSettings($updateSettings);
		$this->setConditionSettings($conditionSettings);
		$this->setOrderSettings($orderSettings);
	}
} 