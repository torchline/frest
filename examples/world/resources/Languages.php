<?php
/**
 * Created by Brad Walker on 6/4/13 at 2:13 PM
*/

require_once(dirname(__FILE__) . '/../../../FREST/Resource.php');

use FREST\Resource;
use FREST\Setting\Settings;
use FREST\Enum\VariableType;

/**
 * Class Languages
 */
class Languages extends Resource {
	public function setup() {	
		$this->setDefaultLimit(10);
		$this->setMaxLimit(200);
		
		$this->setTableSettings(array(
			Settings::table('countrylanguage', array(
					Settings::field('country', 'CountryCode', VariableType::STRING),
					Settings::field('name', 'Language', VariableType::STRING),
					Settings::field('official', 'IsOfficial', VariableType::BOOL),
					Settings::field('percent', 'Percentage', VariableType::FLOAT),
				)
			)	
		));
				
		$this->setReadSettings(array(
			Settings::readField('name'),
			Settings::readField('official'),
			Settings::readField('percent'),
		));
		
		$this->setCreateSettings(array(
			Settings::create('country'),
			Settings::create('name'),
			Settings::create('official', FALSE),
			Settings::create('percent', FALSE),
		));
		
		$this->setUpdateSettings(array(
			Settings::update('name'),
			Settings::update('official'),
			Settings::update('percent'),
		));
		
		$this->setOrderSettings(array(
			Settings::order('country'),
			Settings::order('name'),
			Settings::order('official'),
			Settings::order('percent'),
		));

		$this->setConditionSettings(array(
			Settings::condition('country'),
			Settings::condition('name'),
			Settings::condition('official'),
			Settings::condition('percent'),
		));
	}
}