<?php
/**
 * Created by Brad Walker on 6/4/13 at 2:13 PM
*/

require_once(dirname(__FILE__).'/../../../FREST/resources/FRResource.php');

class Languages extends FRResource {
	public function setup() {	
		$this->setDefaultLimit(10);
		$this->setMaxLimit(200);
		
		$this->setTableSettings(array(
			FRSetting::table('countrylanguage', array(
					FRSetting::field('country', 'CountryCode', FRVariableType::STRING),
					FRSetting::field('name', 'Language', FRVariableType::STRING),
					FRSetting::field('official', 'IsOfficial', FRVariableType::BOOL),
					FRSetting::field('percent', 'Percentage', FRVariableType::FLOAT),
				)
			)	
		));
				
		$this->setReadSettings(array(
			FRSetting::readField('name'),
			FRSetting::readField('official'),
			FRSetting::readField('percent'),
		));
		
		$this->setCreateSettings(array(
			FRSetting::create('country'),
			FRSetting::create('name'),
			FRSetting::create('official', FALSE),
			FRSetting::create('percent', FALSE),
		));
		
		$this->setUpdateSettings(array(
			FRSetting::update('name'),
			FRSetting::update('official'),
			FRSetting::update('percent'),
		));
		
		$this->setOrderSettings(array(
			FRSetting::order('country'),
			FRSetting::order('name'),
			FRSetting::order('official'),
			FRSetting::order('percent'),
		));

		$this->setConditionSettings(array(
			FRSetting::condition('country'),
			FRSetting::condition('name'),
			FRSetting::condition('official'),
			FRSetting::condition('percent'),
		));
	}
}