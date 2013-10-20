<?php
/**
 * Created by Brad Walker on 6/5/13 at 12:06 PM
*/

namespace FREST\Setting;

require_once(dirname(__FILE__) . '/../Setting/Field.php');

/**
 * Class Table
 * @package FREST\Setting
 */
class Table {

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
		/** @var Field $fieldSetting */
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