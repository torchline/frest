<?php
/**
 * Created by Brad Walker on 6/4/13 at 2:13 PM
*/

require_once(dirname(__FILE__).'/../../../FREST/resources/FRResource.php');

class Cities extends FRResource {
	public function setup() {	
		$this->setDefaultLimit(10);
		$this->setMaxLimit(200);
		
		$this->setTableSettings(array(
			FRSetting::table('city', array(
					FRSetting::field('id', 'ID', FRVariableType::INT),
					FRSetting::field('name', 'Name', FRVariableType::STRING),
					FRSetting::field('country', 'CountryCode', FRVariableType::STRING),
					FRSetting::field('district', 'District', FRVariableType::STRING),
					FRSetting::field('population', 'Population', FRVariableType::INT),
				)
			)	
		));
				
		$this->setReadSettings(array(
			FRSetting::readField('id'),
			FRSetting::readField('name'),
			FRSetting::readResource('country', 'Countries', 'code'),
			FRSetting::readField('district'),
			FRSetting::readField('population'),
		));
		
		$this->setCreateSettings(array(
			FRSetting::create('name'),
			FRSetting::create('country'),
			//FRSetting::create('district'),
			//FRSetting::create('population'),
		));
		
		$this->setUpdateSettings(array(
			FRSetting::update('name'),
			FRSetting::update('country'),
			FRSetting::update('district'),
			FRSetting::update('population'),
		));
		
		$this->setOrderSettings(array(
			FRSetting::order('id'),
			FRSetting::order('name'),
			FRSetting::order('country'),
			FRSetting::order('district'),
			FRSetting::order('population'),
		));

		$this->setConditionSettings(array(
			FRSetting::condition('id'),
			FRSetting::condition('name'),
			FRSetting::condition('country'),
			FRSetting::condition('district'),
			FRSetting::condition('population'),
		));
	}
}