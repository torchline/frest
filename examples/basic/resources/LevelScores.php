<?php
/**
 * Created by Brad Walker on 6/4/13 at 2:13 PM
*/

require_once('application/third_party/FREST/resources/FRResource.php');

class LevelScores extends FRResource {
	
	public function setup() {
		$this->setDefaultLimit(10);
		
		$this->setMaxLimit(25);
		
		$this->setTableSettings(array(
			new FRTableSetting('LevelScore', array(
					new FRFieldSetting('UserID', FRVariableType::INT),
					new FRFieldSetting('EpisodeID', FRVariableType::INT),
					new FRFieldSetting('LevelID', FRVariableType::INT),
					new FRFieldSetting('Score', FRVariableType::INT),
					new FRFieldSetting('DateModified', FRVariableType::STRING)
				)
			)	
		));
		
		$this->setAliasSettings(array(
			new FRAliasSetting('user', 'UserID'),
			new FRAliasSetting('episode', 'EpisodeID'),
			new FRAliasSetting('level', 'LevelID'),
			new FRAliasSetting('score', 'Score'),
			new FRAliasSetting('modified', 'DateModified')
		));
		
		$this->setReadSettings(array(
			new FRSingleResourceReadSetting('user', 'Users', 'id', NULL, TRUE),
			new FRFieldReadSetting('episode'),
			new FRFieldReadSetting('level'),
			new FRFieldReadSetting('score'),
			new FRFieldReadSetting('modified', 'sqlDateToTimestamp')
		));
		
		$this->setConditionSettings(array(
			new FRConditionSetting('user'),
			new FRConditionSetting('episode'),
			new FRConditionSetting('level'),
			new FRConditionSetting('score'),
			new FRConditionSetting('modified')
		));
		
		$this->setOrderSettings(array(
			new FROrderSetting('user'),
			new FROrderSetting('episode'),
			new FROrderSetting('level'),
			new FROrderSetting('score'),
			new FROrderSetting('modified')
		));
		
		$this->setCreateSettings(array(
			new FRCreateSetting('episode'),
			new FRCreateSetting('level'),
			new FRCreateSetting('score'),
			new FRCreateSetting('modified')
		));
		
		$this->setUpdateSettings(array(
			new FRUpdateSetting('episode'),
			new FRUpdateSetting('level'),
			new FRUpdateSetting('score'),
			new FRUpdateSetting('modified')
		));
		
		
		$this->setResourceFunctions(array(
			new FRResourceFunction(
				'saveUserScore',
				FALSE,
				FRMethod::PUT,
				array(
					'userID' => new FRFunctionParameter(FRVariableType::INT),
					'score' => new FRFunctionParameter(FRVariableType::INT),
					'episodeID' => new FRFunctionParameter(FRVariableType::INT),
					'levelID' => new FRFunctionParameter(FRVariableType::INT)
				)
			)
		));
	}
	
	
	
	public function saveUserScore($parameters) {
		$userID = $parameters['userID'];
		$score = $parameters['score'];
		$episodeID = $parameters['episodeID'];
		$levelID = $parameters['levelID'];
		
		die("save $userID - $score - $episodeID - $levelID");
	}
	
	
	
	public function isAuthRequiredForRequest($request, &$scopes = NULL) {
		return FALSE;
	}


	// filters
	public function sqlDateToTimestamp($date) {
		return strtotime($date);
	}
}