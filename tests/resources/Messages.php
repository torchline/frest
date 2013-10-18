<?php
/**
 * Created by Brad Walker on 8/13/13 at 3:47 PM
*/

require_once(dirname(__FILE__).'/../../FREST/resources/FRResource.php');

class Messages extends FRResource {
	
	public function setup() {
		$this->setTableSettings(array(
			new FRTableSetting('Message', array(
					new FRFieldSetting('id', 'ID', FRVariableType::INT),
					new FRFieldSetting('sender', 'SenderUserID', FRVariableType::INT),
					new FRFieldSetting('receiver', 'ReceiverUserID', FRVariableType::INT),
					new FRFieldSetting('text', 'Text', FRVariableType::STRING),
					new FRFieldSetting('type', 'Type', FRVariableType::INT),
					new FRFieldSetting('episode', 'EpisodeID', FRVariableType::INT),
					new FRFieldSetting('level', 'LevelID', FRVariableType::INT),
					new FRFieldSetting('amount', 'Amount', FRVariableType::INT),
					new FRFieldSetting('created', 'DateCreated', FRVariableType::STRING)
				)
			)
		));

		$this->modifyReadSettings(array(
			new FRSingularResourceReadSetting('sender', 'Users', 'id', NULL, TRUE),
			new FRSingularResourceReadSetting('receiver', 'Users', 'id', NULL, TRUE),
			new FRFieldReadSetting('created', FRFilter::TIMESTAMP),
		));

		$this->setCreateSettings(array(
			new FRCreateSetting('sender'),
			new FRCreateSetting('receiver'),
			new FRCreateSetting('text'),
			new FRCreateSetting('type'),
			new FRCreateSetting('episode'),
			new FRCreateSetting('level'),
			new FRCreateSetting('amount')
		));
	}
}