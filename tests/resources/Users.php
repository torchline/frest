<?php
/**
 * Created by Brad Walker on 6/4/13 at 2:13 PM
*/

require_once(dirname(__FILE__).'/../../FREST/resources/FRResource.php');

class Users extends FRResource {
	public function setup() {		
		$this->setTableSettings(array(
			FRSetting::table('User', array(
					FRSetting::field('id', 'ID', FRVariableType::INT),
					FRSetting::field('rank', 'RankID', FRVariableType::INT),
					FRSetting::field('token', 'AccessToken', FRVariableType::STRING),
					FRSetting::field('name', 'Name', FRVariableType::STRING),
					FRSetting::field('modified', 'DateModified', FRVariableType::STRING)
				)
			)	
		));
				
		$this->modifyReadSettings(array(
			FRSetting::readResource('rank', 'Ranks', 'id', NULL, TRUE),
			FRSetting::readField('modified', FRFilter::TIMESTAMP),
			FRSetting::readResources('inbox', 'Messages', array('fields' => 'id,text,sender(id,name,rank(name))', 'receiver' => $this->injectValue('id'))),
			FRSetting::readResources('outbox', 'Messages', array('fields' => 'id,text,receiver(id,name)', 'sender' => $this->injectValue('id')))
		));
	}
}