<?php
/**
 * Created by Brad Walker on 6/4/13 at 2:13 PM
*/

require_once(dirname(__FILE__).'/../../FREST/resources/FRResource.php');

class Users extends FRResource {
	
	public function setup() {
		$this->setDefaultLimit(10);
		
		$this->setMaxLimit(25);
		
		$this->setTableSettings(array(
			FRSetting::table('User', array(
					FRSetting::field('ID', FRVariableType::INT),
					FRSetting::field('AccessToken', FRVariableType::STRING),
					FRSetting::field('Name', FRVariableType::STRING),
					FRSetting::field('LevelID', FRVariableType::INT),
					FRSetting::field('DateModified', FRVariableType::STRING)
				)
			)	
		));
		
		$this->setAliasSettings(array(
			FRSetting::alias('id', 'ID'),
			FRSetting::alias('token', 'AccessToken'),
			FRSetting::alias('name', 'Name'),
			FRSetting::alias('level', 'LevelID'),
			FRSetting::alias('modified', 'DateModified')
		));
		
		$this->setReadSettings(array(
			FRSetting::readField('id'),
			FRSetting::readField('token'),
			FRSetting::readField('name'),
			FRSetting::readField('level', NULL),
			FRSetting::readField('modified', 'sqlDateToTimestamp')
		));
		
		$this->setConditionSettings(array(
			FRSetting::condition('id'),
			FRSetting::condition('name'),
			FRSetting::condition('level'),
			FRSetting::condition('modified'),
		));
		
		$this->setOrderSettings(array(
			FRSetting::order('id'),
			FRSetting::order('rank'),
			FRSetting::order('username'),
			FRSetting::order('modified')
		));
		
		$this->setCreateSettings(array(
			FRSetting::create('id'),
			FRSetting::create('token'),
			FRSetting::create('name'),
			FRSetting::create('level'),
			FRSetting::create('modified')
		));
		
		$this->setUpdateSettings(array(
			FRSetting::update('token'),
			FRSetting::update('name'),
			FRSetting::update('level'),
			FRSetting::update('modified')
		));
		
		
		
		$this->setResourceFunctions(array(
			FRFunction::resource(
				'friends',
				TRUE,
				FRMethod::GET,
				NULL
			),
			FRFunction::resource(
				'friendsLevelScores',
				TRUE,
				FRMethod::GET,
				array(
					'levelID' => FRFunction::parameter(FRVariableType::INT),
					'limit' => FRFunction::parameter(FRVariableType::INT, FALSE)
				)
			),
			FRFunction::resource(
				'friendsEpisodeScores',
				TRUE,
				FRMethod::GET,
				array(
					'episodeID' => FRFunction::parameter(FRVariableType::INT),
					'limit' => FRFunction::parameter(FRVariableType::INT, FALSE)
				)
			)
		));
	}
	
	
	// api functions
	/**
	 * @param array $parameters
	 * @return FRResult
	 */
	public function friends($parameters) {
		$id = $parameters['resourceID'];

		$user = $this->getUserWithID($id, $error);
		if (isset($error)) {
			return $error;
		}

		$pdo = $this->getPDO();

		$facebook = new Facebook(array(
			'appId' => '190282547790980',
			'secret' => '245fc54eef709c896b1bba504b0a7927'
		));
		$facebook->setAccessToken($user->AccessToken);

		$fbFriendsResult = $facebook->api('/me/friends');
		$fbFriends = $fbFriendsResult['data'];

		$fbFriendIDs = array();
		foreach ($fbFriends as $fbFriend) {
			$fbFriendID = intval($fbFriend['id']);
			if (strlen($fbFriendID) > 0) {
				$fbFriendIDs[] = $fbFriendID;
			}
		}
		$fbFriendIDString = implode(',', $fbFriendIDs);

		// get friends from fb friend id list
		$sql = "SELECT ID, AccessToken, Name, LevelID, DateModified FROM User WHERE ID IN ({$fbFriendIDString})";
		$userStmt = $pdo->query($sql);

		if (!$userStmt->execute()) {
			$error = new FRErrorResult(FRErrorResult::SQLError, 500, implode(' ', $userStmt->errorInfo()));
			return $error;
		}

		$friends = $userStmt->fetchAll(PDO::FETCH_OBJ);

		$formattedFriends = array();
		foreach ($friends as $friend) {
			$formattedFriend = $this->formatResourceObject($friend);
			$formattedFriend->modified = $this->sqlDateToTimestamp($formattedFriend->modified);
			
			$formattedFriends[] = $formattedFriend;
		}
		
		return new FRResourceFunctionResult(200, $formattedFriends);
	}


	/**
	 * @param array $parameters
	 * @return FRResult
	 */
	public function friendsLevelScores($parameters) {
		$id = $parameters['resourceID'];
		$levelID = $parameters['levelID'];
		$limit = isset($parameters['limit']) ? $parameters['limit'] : 5;

		$user = $this->getUserWithID($id, $error);
		if (isset($error)) {
			return $error;
		}

		$pdo = $this->getPDO();

		$facebook = new Facebook(array(
			'appId' => '190282547790980',
			'secret' => '245fc54eef709c896b1bba504b0a7927'
		));
		$facebook->setAccessToken($user->AccessToken);

		$fbFriendsResult = $facebook->api('/me/friends');
		$fbFriends = $fbFriendsResult['data'];

		$fbFriendIDs = array();
		foreach ($fbFriends as $fbFriend) {
			$fbFriendID = intval($fbFriend['id']);
			if (strlen($fbFriendID) > 0) {
				$fbFriendIDs[] = $fbFriendID;
			}
		}
		$fbFriendIDString = implode(',', $fbFriendIDs);

		// get friends from fb friend id list
		$sql = "SELECT u.ID, u.AccessToken, u.Name, u.LevelID, u.DateModified, ls.Score FROM User u INNER JOIN LevelScore ls ON u.ID = ls.UserID WHERE u.ID IN ({$fbFriendIDString}) && ls.LevelID = :levelID LIMIT :limit";
		$userStmt = $pdo->prepare($sql);

		$userStmt->bindValue(
			':levelID',
			$levelID,
			PDO::PARAM_INT
		);

		$userStmt->bindValue(
			':limit',
			$limit,
			PDO::PARAM_INT
		);

		if (!$userStmt->execute()) {
			$error = new FRErrorResult(FRErrorResult::SQLError, 500, implode(' ', $userStmt->errorInfo()));
			return $error;
		}

		$friends = $userStmt->fetchAll(PDO::FETCH_OBJ);

		$formattedFriends = array();
		foreach ($friends as $friend) {
			$formattedFriend = $this->formatResourceObject($friend);
			$formattedFriend->score = $friend->Score;

			$formattedFriends[] = $formattedFriend;
		}

		return new FRResourceFunctionResult(200, $formattedFriends);
	}



	/**
	 * @param array $parameters
	 * @return FRResult
	 */
	public function friendsEpisodeScores($parameters) {
		$id = $parameters['resourceID'];
		$episodeID = $parameters['episodeID'];
		$limit = isset($parameters['limit']) ? $parameters['limit'] : 5;

		$user = $this->getUserWithID($id, $error);
		if (isset($error)) {
			return $error;
		}

		$pdo = $this->getPDO();

		$facebook = new Facebook(array(
			'appId' => '190282547790980',
			'secret' => '245fc54eef709c896b1bba504b0a7927'
		));
		$facebook->setAccessToken($user->AccessToken);

		$fbFriendsResult = $facebook->api('/me/friends');
		$fbFriends = $fbFriendsResult['data'];

		$fbFriendIDs = array();
		foreach ($fbFriends as $fbFriend) {
			$fbFriendID = intval($fbFriend['id']);
			if (strlen($fbFriendID) > 0) {
				$fbFriendIDs[] = $fbFriendID;
			}
		}
		$fbFriendIDString = implode(',', $fbFriendIDs);

		// get friends from fb friend id list
		$sql = "SELECT u.ID, u.AccessToken, u.Name, u.LevelID, u.DateModified, es.Score FROM User u INNER JOIN EpisodeScore es ON u.ID = es.UserID WHERE u.ID IN ({$fbFriendIDString}) && es.EpisodeID = :episodeID LIMIT :limit";
		$userStmt = $pdo->prepare($sql);

		$userStmt->bindValue(
			':episodeID',
			$episodeID,
			PDO::PARAM_INT
		);

		$userStmt->bindValue(
			':limit',
			$limit,
			PDO::PARAM_INT
		);

		if (!$userStmt->execute()) {
			$error = new FRErrorResult(FRErrorResult::SQLError, 500, implode(' ', $userStmt->errorInfo()));
			return $error;
		}

		$friends = $userStmt->fetchAll(PDO::FETCH_OBJ);

		$formattedFriends = array();
		foreach ($friends as $friend) {
			$formattedFriend = $this->formatResourceObject($friend);
			$formattedFriend->score = $friend->Score;

			$formattedFriends[] = $formattedFriend;
		}

		return new FRResourceFunctionResult(200, $formattedFriends);
	}
	
	
	
	/**
	 * @param int $id
	 * @param FRErrorResult $error
	 * @return stdClass
	 */
	private function getUserWithID($id, &$error = NULL) {
		$pdo = $this->getPDO();

		$sql = "SELECT ID, AccessToken, Name, LevelID, DateModified FROM User WHERE ID = :id LIMIT 1";
		$userStmt = $pdo->prepare($sql);

		$userStmt->bindValue(
			':id',
			$id,
			PDO::PARAM_INT
		);

		if (!$userStmt->execute()) {
			$error = new FRErrorResult(FRErrorResult::SQLError, 500, implode(' ', $userStmt->errorInfo()));
			return NULL;
		}

		$users = $userStmt->fetchAll(PDO::FETCH_OBJ);

		if (count($users) == 0) {
			$error = new FRErrorResult(FRErrorResult::NoResults, 404, "No user with id '{$id}' found");
			return NULL;
		}

		return $users[0];
	}

	
	
	
	// filters
	public function sqlDateToTimestamp($date) {
		return strtotime($date);
	}



	public function isAuthRequiredForRequest($request, &$scopes = NULL) {
		return FALSE;
	}
}