<?php
/**
 * Created by Brad Walker on 5/16/13 at 4:52 AM
*/

/**
 * Conforms to OAuth 2.0 Draft 31
 * 
 * @property string $accessToken
 * @property string $clientID
 * @property int $expires
 * @property string $type
 * @property string $userID
 * @property string $scope
 * 
 * Class OAuthAccessToken
 */
class OAuth2AccessTokenObject {
	
	const TYPE_BEARER = 'bearer';
	const TYPE_MAC = 'mac';
	
	public $accessToken;
	public $clientID;
	public $expires;
	public $type;
	public $userID;
	public $scopes;


	/**
	 * @param string $accessToken
	 * @param string $clientID
	 * @param int $expires
	 * @param string $type
	 * @param string $userID
	 * @param string $scope
	 */
	public function __construct($accessToken, $clientID, $expires, $type, $userID = '', $scope = '') {
		$this->accessToken = $accessToken;
		$this->clientID= $clientID;
		$this->expires = $expires;
		$this->type = $type;
		$this->userID = $userID;
		$this->scope = $scope;
	}


	/**
	 * @return array
	 */
	public function getResponse() {
		$response['access_token'] = $this->accessToken;
		$response['token_type'] = $this->type;
		$response['expires_in'] = $this->expires - time();
		
		return $response;
	}
}