<?php
/**
 * Created by Brad Walker on 8/13/13 at 3:47 PM
*/

require_once(dirname(__FILE__).'/../../../FREST/resources/FRResource.php');

class Messages extends FRResource {
	
	public function setup() {
		$this->setTableSettings(array(
			FRSetting::table('message', array(
					FRSetting::field('id', 'ID', FRVariableType::INT),
					FRSetting::field('sender', 'SenderUserID', FRVariableType::INT),
					FRSetting::field('receiver', 'ReceiverUserID', FRVariableType::INT),
					FRSetting::field('text', 'Text', FRVariableType::STRING),
					FRSetting::field('created', 'DateCreated', FRVariableType::STRING),
				)
			)
		));

		$this->setReadSettings(array(
			FRSetting::readField('id'),
			FRSetting::readResource('sender', 'Users', 'id'),
			FRSetting::readResource('receiver', 'Users', 'id'),
			FRSetting::readField('text'),
			FRSetting::readField('created', FRFilter::SQL_DATE_TO_TIMESTAMP),
		));

		$this->setCreateSettings(array(
			FRSetting::create('sender'),
			FRSetting::create('receiver'),
			FRSetting::create('text'),
		));
	}
}