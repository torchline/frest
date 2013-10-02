<?php
/**
 * Created by Brad Walker on 8/13/13 at 3:47 PM
*/

require_once(dirname(__FILE__).'/../../FREST/resources/FRResource.php');

class Messages extends FRResource {
	
	public function setup() {
		$this->setDefaultLimit(10);

		$this->setMaxLimit(25);

		$this->setTableSettings(array(
			new FRTableSetting('Message', array(
					new FRFieldSetting('ID', FRVariableType::INT),
					new FRFieldSetting('SenderUserID', FRVariableType::INT),
					new FRFieldSetting('ReceiverUserID', FRVariableType::INT),
					new FRFieldSetting('Text', FRVariableType::STRING),
					new FRFieldSetting('Type', FRVariableType::INT),
					new FRFieldSetting('EpisodeID', FRVariableType::INT),
					new FRFieldSetting('LevelID', FRVariableType::INT),
					new FRFieldSetting('Amount', FRVariableType::INT),
					new FRFieldSetting('DateCreated', FRVariableType::STRING)
				)
			)
		));

		$this->setAliasSettings(array(
			new FRAliasSetting('id', 'ID'),
			new FRAliasSetting('sender', 'SenderUserID'),
			new FRAliasSetting('receiver', 'ReceiverUserID'),
			new FRAliasSetting('text', 'Text'),
			new FRAliasSetting('type', 'Type'),
			new FRAliasSetting('episode', 'EpisodeID'),
			new FRAliasSetting('level', 'LevelID'),
			new FRAliasSetting('amount', 'Amount'),
			new FRAliasSetting('created', 'DateCreated')
		));

		$this->setReadSettings(array(
			new FRFieldReadSetting('id'),
			new FRSingleResourceReadSetting('sender', 'Users', 'id', NULL, TRUE),
			new FRSingleResourceReadSetting('receiver', 'Users', 'id', NULL, TRUE),
			new FRFieldReadSetting('text'),
			new FRFieldReadSetting('type'),
			new FRFieldReadSetting('episode'),
			new FRFieldReadSetting('level'),
			new FRFieldReadSetting('amount'),
			new FRFieldReadSetting('created', 'sqlDateToTimestamp'),
		));

		$this->setConditionSettings(array(
			new FRConditionSetting('id'),
			new FRConditionSetting('sender'),
			new FRConditionSetting('receiver'),
			new FRConditionSetting('text'),
			new FRConditionSetting('type'),
			new FRConditionSetting('episode'),
			new FRConditionSetting('level'),
			new FRConditionSetting('amount'),
			new FRConditionSetting('created'),
		));

		$this->setOrderSettings(array(
			new FROrderSetting('id'),
			new FROrderSetting('sender'),
			new FROrderSetting('receiver'),
			new FROrderSetting('text'),
			new FROrderSetting('type'),
			new FROrderSetting('episode'),
			new FROrderSetting('level'),
			new FROrderSetting('amount'),
			new FROrderSetting('created')
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

	public function isAuthRequiredForRequest($request, &$scopes = NULL) {
		return FALSE;
	}
	
	
	// filters
	public function sqlDateToTimestamp($date) {
		return strtotime($date);
	}
}