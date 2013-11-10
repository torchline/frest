<?php
/**
 * Created by Brad Walker on 6/4/13 at 2:13 PM
*/

use FREST\Resource;
use FREST\Setting\Settings;
use FREST\Type\Variable;

/**
 * Class Cities
 */
class Cities extends Resource {
	public function setup() {	
		$this->setDefaultLimit(10);
		$this->setMaxLimit(200);
		
		$this->setTableSettings(array(
			Settings::table('city', array(
					Settings::field('id', 'ID', Variable::INT),
					Settings::field('name', 'Name', Variable::STRING),
					Settings::field('country', 'CountryCode', Variable::STRING),
					Settings::field('district', 'District', Variable::STRING),
					Settings::field('population', 'Population', Variable::INT),
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