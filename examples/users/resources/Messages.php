<?php
/**
 * Created by Brad Walker on 8/13/13 at 3:47 PM
*/

require_once(dirname(__FILE__) . '/../../../FREST/Resource.php');

use FREST\Resource;
use FREST\Setting\Settings;
use FREST\Enum\VariableType;
use FREST\Enum\Filter;

/**
 * Class Messages
 */
class Messages extends Resource {
	
	public function setup() {
		$this->setTableSettings(array(
			Settings::table('message', array(
					Settings::field('id', 'ID', VariableType::INT),
					Settings::field('sender', 'SenderUserID', VariableType::INT),
					Settings::field('receiver', 'ReceiverUserID', VariableType::INT),
					Settings::field('text', 'Text', VariableType::STRING),
					Settings::field('created', 'DateCreated', VariableType::STRING),
				)
			)
		));

		$this->setReadSettings(array(
			Settings::readField('id'),
			Settings::readResource('sender', 'Users', 'id'),
			Settings::readResource('receiver', 'Users', 'id'),
			Settings::readField('text'),
			Settings::readField('created', Filter::SQL_DATE_TO_TIMESTAMP),
		));

		$this->setCreateSettings(array(
			Settings::create('sender'),
			Settings::create('receiver'),
			Settings::create('text'),
		));
	}
}