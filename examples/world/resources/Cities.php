<?php
/**
 * Created by Brad Walker on 6/4/13 at 2:13 PM
*/

require_once(dirname(__FILE__) . '/../../../FREST/Resource.php');

use FREST\Resource;
use FREST\Setting\Settings;
use FREST\Enum\VariableType;

/**
 * Class Cities
 */
class Cities extends Resource {
	public function setup() {	
		$this->setDefaultLimit(10);
		$this->setMaxLimit(200);
		
		$this->setTableSettings(array(
			Settings::table('city', array(
					Settings::field('id', 'ID', VariableType::INT),
					Settings::field('name', 'Name', VariableType::STRING),
					Settings::field('country', 'CountryCode', VariableType::STRING),
					Settings::field('district', 'District', VariableType::STRING),
					Settings::field('population', 'Population', VariableType::INT),
				)
			)	
		));
				
		$this->setReadSettings(array(
			Settings::readField('id'),
			Settings::readField('name'),
			Settings::readResource('country', 'Countries', 'code'),
			Settings::readField('district'),
			Settings::readField('population'),
		));
		
		$this->setCreateSettings(array(
			Settings::create('name'),
			Settings::create('country'),
			//Settings::create('district'),
			//Settings::create('population'),
		));
		
		$this->setUpdateSettings(array(
			Settings::update('name'),
			Settings::update('country'),
			Settings::update('district'),
			Settings::update('population'),
		));
		
		$this->setOrderSettings(array(
			Settings::order('id'),
			Settings::order('name'),
			Settings::order('country'),
			Settings::order('district'),
			Settings::order('population'),
		));

		$this->setConditionSettings(array(
			Settings::condition('id'),
			Settings::condition('name'),
			Settings::condition('country'),
			Settings::condition('district'),
			Settings::condition('population'),
		));
	}
}