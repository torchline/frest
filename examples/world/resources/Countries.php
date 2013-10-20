<?php
/**
 * Created by Brad Walker on 6/4/13 at 2:13 PM
*/

require_once(dirname(__FILE__) . '/../../../FREST/Resource.php');

use FREST\Resource;
use FREST\Setting\Settings;
use FREST\Enum\VariableType;

/**
 * Class Countries
 */
class Countries extends Resource {
	public function setup() {
		$this->setDefaultLimit(10);
		$this->setMaxLimit(200);
		
		$this->setTableSettings(array(
			Settings::table('country', array(
					Settings::field('code', 'Code', VariableType::STRING),
					Settings::field('name', 'Name', VariableType::STRING),
					Settings::field('continent', 'Continent', VariableType::STRING),
					Settings::field('region', 'Region', VariableType::STRING),
					Settings::field('surfaceArea', 'SurfaceArea', VariableType::FLOAT),
					Settings::field('independence', 'IndepYear', VariableType::INT),
					Settings::field('population', 'Population', VariableType::INT),
					Settings::field('lifeExpectancy', 'LifeExpectancy', VariableType::FLOAT),
					Settings::field('gnp', 'GNP', VariableType::FLOAT),
					Settings::field('gnpOld', 'GNPOld', VariableType::FLOAT),
					Settings::field('localName', 'LocalName', VariableType::STRING),
					Settings::field('govForm', 'GovernmentForm', VariableType::STRING),
					Settings::field('headOfState', 'HeadOfState', VariableType::STRING),
					Settings::field('capital', 'Capital', VariableType::INT),
					Settings::field('code2', 'Code2', VariableType::STRING),
				)
			)	
		));
				
		$this->setReadSettings(array(
			Settings::readField('code'),
			Settings::readField('name'),
			Settings::readField('continent'),
			Settings::readField('region', NULL, FALSE),
			Settings::readField('surfaceArea', NULL, FALSE),
			Settings::readField('independence', NULL, FALSE),
			Settings::readField('population', NULL, FALSE),
			Settings::readField('lifeExpectancy', NULL, FALSE),
			Settings::readField('gnp', NULL, FALSE),
			Settings::readField('gnpOld', NULL, FALSE),
			Settings::readField('localName', NULL, FALSE),
			Settings::readField('govForm', NULL, FALSE),
			Settings::readField('headOfState', NULL, FALSE),
			Settings::readField('capital', NULL, FALSE),
			Settings::readField('code2', NULL, FALSE),
			
			Settings::readResources('langs', 'Languages', array('country' => $this->injectValue('code'))),
			Settings::readResources('mainLangs', 'Languages', array('country' => $this->injectValue('code'), 'percent' => 'gt(10)')),
		));
		
		$this->setCreateSettings(array(
			Settings::create('code'),
			Settings::create('name'),
			Settings::create('continent'),
			Settings::create('region'),
			Settings::create('surfaceArea'),
			Settings::create('independence'),
			Settings::create('population'),
			Settings::create('lifeExpectancy'),
			Settings::create('gnp'),
			Settings::create('gnpOld'),
			Settings::create('localName'),
			Settings::create('govForm'),
			Settings::create('headOfState'),
			Settings::create('capital'),
			Settings::create('code2'),
		));
		
		$this->setUpdateSettings(array(
			Settings::update('code'),
			Settings::update('name'),
			Settings::update('continent'),
			Settings::update('region'),
			Settings::update('surfaceArea'),
			Settings::update('independence'),
			Settings::update('population'),
			Settings::update('lifeExpectancy'),
			Settings::update('gnp'),
			Settings::update('gnpOld'),
			Settings::update('localName'),
			Settings::update('govForm'),
			Settings::update('headOfState'),
			Settings::update('capital'),
			Settings::update('code2'),
		));
		
		$this->setOrderSettings(array(
			Settings::order('code'),
			Settings::order('name'),
			Settings::order('continent'),
			Settings::order('region'),
			Settings::order('surfaceArea'),
			Settings::order('independence'),
			Settings::order('population'),
			Settings::order('lifeExpectancy'),
			Settings::order('gnp'),
			Settings::order('gnpOld'),
			Settings::order('localName'),
			Settings::order('govForm'),
			Settings::order('headOfState'),
			Settings::order('capital'),
			Settings::order('code2'),
		));

		$this->setConditionSettings(array(
			Settings::condition('code'),
			Settings::condition('name'),
			Settings::condition('continent'),
			Settings::condition('region'),
			Settings::condition('surfaceArea'),
			Settings::condition('independence'),
			Settings::condition('population'),
			Settings::condition('lifeExpectancy'),
			Settings::condition('gnp'),
			Settings::condition('gnpOld'),
			Settings::condition('localName'),
			Settings::condition('govForm'),
			Settings::condition('headOfState'),
			Settings::condition('capital'),
			Settings::condition('code2'),
		));
	}
}