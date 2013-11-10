<?php
/**
 * Created by Brad Walker on 6/4/13 at 2:13 PM
*/

use FREST\Resource;
use FREST\Setting\Settings;
use FREST\Type\Filter;
use FREST\Type\Variable;

/**
 * Class Users
 */
class Users extends Resource {
	public function setup() {		
		$this->setTableSettings(array(
			Settings::table('user', array(
					Settings::field('id', 'ID', Variable::INT),
					Settings::field('rank', 'RankID', Variable::INT),
					Settings::field('token', 'AccessToken', Variable::STRING),
					Settings::field('name', 'Name', Variable::STRING),
					Settings::field('modified', 'DateModified', Variable::STRING),
				)
			)	
		));
				
		$this->modifyReadSettings(array(
			Settings::readResource('rank', 'Ranks', 'id', NULL, TRUE),
			Settings::readField('modified', Filter::SQL_DATE_TO_TIMESTAMP),
			Settings::readResources('inbox', 'Messages', array('fields' => 'id,text,sender(id,name,rank(name))', 'receiver' => $this->injectValue('id'))),
			Settings::readResources('outbox', 'Messages', array('fields' => 'id,text,receiver(id,name)', 'sender' => $this->injectValue('id'))),
		));
		
		$this->setCreateSettings(array(
			Settings::create('rank', FALSE),
			Settings::create('token', FALSE),
			Settings::create('name'),
		));
		
		$this->setUpdateSettings(array(
			Settings::update('rank'),
			Settings::update('token'),
			Settings::update('name'),
		));
		
		$this->setOrderSettings(array(
			Settings::order('id'),
			Settings::order('rank'),
			Settings::order('name'),
			Settings::order('modified'),
		));
	}
}