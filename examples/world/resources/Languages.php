<?php
/**
 * Created by Brad Walker on 6/4/13 at 2:13 PM
*/

use FREST\Resource;
use FREST\Setting\Settings;
use FREST\Type\Variable;

/**
 * Class Languages
 */
class Languages extends Resource {
	public function setup() {	
		$this->setDefaultLimit(10);
		$this->setMaxLimit(200);
		
		$this->setTableSettings(array(
			Settings::table('countrylanguage', array(
					Settings::field('country', 'CountryCode', Variable::STRING),
					Settings::field('name', 'Language', Variable::STRING),
					Settings::field('official', 'IsOfficial', Variable::BOOL),
					Settings::field('percent', 'Percentage', Variable::FLOAT),
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