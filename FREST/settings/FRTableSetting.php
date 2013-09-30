<?php
/**
 * Created by Brad Walker on 6/5/13 at 12:06 PM
*/

require_once(dirname(__FILE__).'/../settings/FRFieldSetting.php');

/**
 * Class FRTableSetting
 */
class FRTableSetting {

	/** @var string */
	protected $table;
	
	/** @var array */
	protected $fieldSettings;



	/**
	 * @param string $table
	 * @param array $fieldSettings
	 */
	function __construct($table, $fieldSettings) {
		$this->table = $table;
		
		$keyedFieldSettings = array();
		/** @var FRFieldSetting $fieldSetting */
		foreach ($fieldSettings as $fieldSetting) {
			$keyedFieldSettings[$fieldSetting->getField()] = $fieldSetting;
		}
		$this->fieldSettings = $keyedFieldSettings;
	}



	/**
	 * @return string
	 */
	public function getTable() {
		return $this->table;
	}
	
	/**
	 * @return array
	 */
	public function getFieldSettings() {
		return $this->fieldSettings;
	}

}