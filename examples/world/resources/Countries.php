<?php
/**
 * Created by Brad Walker on 6/4/13 at 2:13 PM
*/

require_once(dirname(__FILE__).'/../../../FREST/resources/FRResource.php');
class Countries extends FRResource {
	public function setup() {
		$this->setDefaultLimit(10);
		$this->setMaxLimit(200);
		
		$this->setTableSettings(array(
			FRSetting::table('country', array(
					FRSetting::field('code', 'Code', FRVariableType::STRING),
					FRSetting::field('name', 'Name', FRVariableType::STRING),
					FRSetting::field('continent', 'Continent', FRVariableType::STRING),
					FRSetting::field('region', 'Region', FRVariableType::STRING),
					FRSetting::field('surfaceArea', 'SurfaceArea', FRVariableType::FLOAT),
					FRSetting::field('independence', 'IndepYear', FRVariableType::INT),
					FRSetting::field('population', 'Population', FRVariableType::INT),
					FRSetting::field('lifeExpectancy', 'LifeExpectancy', FRVariableType::FLOAT),
					FRSetting::field('gnp', 'GNP', FRVariableType::FLOAT),
					FRSetting::field('gnpOld', 'GNPOld', FRVariableType::FLOAT),
					FRSetting::field('localName', 'LocalName', FRVariableType::STRING),
					FRSetting::field('govForm', 'GovernmentForm', FRVariableType::STRING),
					FRSetting::field('headOfState', 'HeadOfState', FRVariableType::STRING),
					FRSetting::field('capital', 'Capital', FRVariableType::INT),
					FRSetting::field('code2', 'Code2', FRVariableType::STRING),
				)
			)	
		));
				
		$this->setReadSettings(array(
			FRSetting::readField('code'),
			FRSetting::readField('name'),
			FRSetting::readField('continent'),
			FRSetting::readField('region', NULL, FALSE),
			FRSetting::readField('surfaceArea', NULL, FALSE),
			FRSetting::readField('independence', NULL, FALSE),
			FRSetting::readField('population', NULL, FALSE),
			FRSetting::readField('lifeExpectancy', NULL, FALSE),
			FRSetting::readField('gnp', NULL, FALSE),
			FRSetting::readField('gnpOld', NULL, FALSE),
			FRSetting::readField('localName', NULL, FALSE),
			FRSetting::readField('govForm', NULL, FALSE),
			FRSetting::readField('headOfState', NULL, FALSE),
			FRSetting::readField('capital', NULL, FALSE),
			FRSetting::readField('code2', NULL, FALSE),
			
			FRSetting::readResources('langs', 'Languages', array('country' => $this->injectValue('code'))),
			FRSetting::readResources('mainLangs', 'Languages', array('country' => $this->injectValue('code'), 'percent' => 'gt(10)')),
		));
		
		$this->setCreateSettings(array(
			FRSetting::create('code'),
			FRSetting::create('name'),
			FRSetting::create('continent'),
			FRSetting::create('region'),
			FRSetting::create('surfaceArea'),
			FRSetting::create('independence'),
			FRSetting::create('population'),
			FRSetting::create('lifeExpectancy'),
			FRSetting::create('gnp'),
			FRSetting::create('gnpOld'),
			FRSetting::create('localName'),
			FRSetting::create('govForm'),
			FRSetting::create('headOfState'),
			FRSetting::create('capital'),
			FRSetting::create('code2'),
		));
		
		$this->setUpdateSettings(array(
			FRSetting::update('code'),
			FRSetting::update('name'),
			FRSetting::update('continent'),
			FRSetting::update('region'),
			FRSetting::update('surfaceArea'),
			FRSetting::update('independence'),
			FRSetting::update('population'),
			FRSetting::update('lifeExpectancy'),
			FRSetting::update('gnp'),
			FRSetting::update('gnpOld'),
			FRSetting::update('localName'),
			FRSetting::update('govForm'),
			FRSetting::update('headOfState'),
			FRSetting::update('capital'),
			FRSetting::update('code2'),
		));
		
		$this->setOrderSettings(array(
			FRSetting::order('code'),
			FRSetting::order('name'),
			FRSetting::order('continent'),
			FRSetting::order('region'),
			FRSetting::order('surfaceArea'),
			FRSetting::order('independence'),
			FRSetting::order('population'),
			FRSetting::order('lifeExpectancy'),
			FRSetting::order('gnp'),
			FRSetting::order('gnpOld'),
			FRSetting::order('localName'),
			FRSetting::order('govForm'),
			FRSetting::order('headOfState'),
			FRSetting::order('capital'),
			FRSetting::order('code2'),
		));

		$this->setConditionSettings(array(
			FRSetting::condition('code'),
			FRSetting::condition('name'),
			FRSetting::condition('continent'),
			FRSetting::condition('region'),
			FRSetting::condition('surfaceArea'),
			FRSetting::condition('independence'),
			FRSetting::condition('population'),
			FRSetting::condition('lifeExpectancy'),
			FRSetting::condition('gnp'),
			FRSetting::condition('gnpOld'),
			FRSetting::condition('localName'),
			FRSetting::condition('govForm'),
			FRSetting::condition('headOfState'),
			FRSetting::condition('capital'),
			FRSetting::condition('code2'),
		));
	}
}