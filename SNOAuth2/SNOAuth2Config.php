<?php
/**
 * Created by Brad Walker on 5/15/13 at 8:33 PM
*/

require_once('SNOAuth2DelegateInterface.php');
require_once('SNOAuth2.php');

/** 
 * @property PDO $pdo
 * @property SNOAuth2DelegateInterface $delegate
 * 
 * Class OAuth2Config
 */
class SNOAuth2Config {
	
	public $pdo;
	public $delegate;
	
	public $showErrorDescriptions = TRUE;
	public $showErrorURIs = TRUE;
	public $accessTokenLifetime = 3600;
	

	public $grantParameterLocations = array(
		ParameterLocation::BODY,
		ParameterLocation::URL
	);
	
	public $grantClientCredentialLocations = array(
		CredentialLocation::HEADER,
		CredentialLocation::BODY,
		CredentialLocation::URL
	);

	public $resourceAccessTokenLocations = array(
		AccessTokenLocation::HEADER_BEARER,
		AccessTokenLocation::BODY,
		AccessTokenLocation::URL
	);
	
	
	public $clientsTable = array(
		'name' => 'OAuthClient',
		'fields' => array(
			'id' => 'ID',
			'secret' => 'Secret',
			'redirectURI' => 'RedirectURI'
		)
	);

	public $accessTokensTable = array(
		'name' => 'OAuthAccessToken',
		'fields' => array(
			'clientID' => 'ClientID',
			'accessToken' => 'AccessToken',
			'expires' => 'Expires',
			'userID' => 'UserID',
			'scope' => 'Scope'
		)
	);
	
	
	/**
	 * @param PDO $pdo
	 */
	public function __construct(PDO $pdo) {
		$this->pdo = $pdo;
	}
}