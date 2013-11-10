<?php
/**
 * Created by Brad Walker on 8/13/13 at 3:47 PM
*/

use FREST\Resource;
use FREST\Setting\Settings;
use FREST\Type\Variable;
use FREST\Type\Filter;

/**
 * Class Messages
 */
class Messages extends Resource {
	
	public function setup() {
		$this->setTableSettings(array(
			Settings::table('message', array(
					Settings::field('id', 'ID', Variable::INT),
					Settings::field('sender', 'SenderUserID', Variable::INT),
					Settings::field('receiver', 'ReceiverUserID', Variable::INT),
					Settings::field('text', 'Text', Variable::STRING),
					Settings::field('created', 'DateCreated', Variable::STRING),
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