<?php
/**
 * Created by Brad Walker on 5/15/13 at 7:19 PM
*/

require_once('SNOAuth2.php');

/**
 * Conforms to OAuth 2.0 Draft 31
 * 
 * Class SNOAuth2ClientCredentials
 */
class SNOAuth2_ClientCredentials extends SNOAuth2 {

	/**
	 * Outputs new access token response or error response
	 */
	public function deliverAccessToken() {
		$parameterLocations = $this->bitwiseORArray($this->config->grantParameterLocations);
		
		$grantType = $this->getParameterValue(
			'grant_type',
			$parameterLocations
		);
		
		if (!isset($grantType)) {
			$error = new OAuth2Error(OAuth2Error::INVALID_GRANT, "grant_type was not defined");
			$this->outputError($error);
		}
		if ($grantType != OAuth2GrantType::CLIENT_CREDENTIALS) {
			$error = new OAuth2Error(OAuth2Error::INVALID_GRANT, 'grant_type specified is either invalid or not allowed');
			$this->outputError($error);
		}
		
		$credentials = $this->getClientCredentials(
			$this->bitwiseORArray($this->config->grantClientCredentialLocations)
		);
		
		$clientID = $credentials['client_id'];
		$clientSecret = $credentials['client_secret'];
		
		$scope = $this->getParameterValue(
			'scope', 
			$parameterLocations
		);
		if (!isset($scope)) {
			$scope = '';
		}
		
		$areCredentialsValid = $this->verifyClientCredentials(
			$clientID,
			$clientSecret
		);
		
		if (!$areCredentialsValid) {
			$error = new OAuth2Error(OAuth2Error::INVALID_CLIENT, 'client credentials are invalid');
			$this->outputError($error);
		}

		// return existing token
		$accessTokenObject = $this->getExistingAccessTokenObject($clientID, '', $scope);

		if (!isset($accessTokenObject)) {
			// generate new access token
			$accessTokenObject = new OAuth2AccessTokenObject(
				$this->generateAccessToken(),
				$clientID,
				time() + $this->config->accessTokenLifetime,
				OAuth2AccessTokenObject::TYPE_BEARER,
				'',
				$scope
			);

			$this->saveAccessTokenObject($accessTokenObject);
		}

		$this->outputAccessTokenObject($accessTokenObject);			
	}

	/**
	 * @param string $scope
	 */
	public function verifyResourceAccess($scope = '') {
		$combinedLocations = $this->bitwiseORArray($this->config->resourceAccessTokenLocations);
		
		$accessToken = $this->getAccessToken($combinedLocations);
		$isValidAccessToken = $this->verifyAccessToken($accessToken, $scope);
		
		if (!$isValidAccessToken) {
			$error = new OAuth2Error(OAuth2Error::INVALID_GRANT, 'the access token supplied is invalid');
			$this->outputError($error);
		}
	}

	/**
	 * @param string $clientID
	 * @param string $userID
	 * @param string $scope
	 * 
	 * @return OAuth2AccessTokenObject|NULL
	 */
	protected function getExistingAccessTokenObject($clientID, $userID = '', $scope = '') {
		$stmt = $this->config->pdo->prepare(sprintf(
			'SELECT %s, %s, %s FROM %s WHERE %s = :clientID AND %s = :userID AND %s = :scope',
			$this->config->accessTokensTable['fields']['accessToken'],
			$this->config->accessTokensTable['fields']['expires'],
			$this->config->accessTokensTable['fields']['scope'],
			$this->config->accessTokensTable['name'],
			$this->config->accessTokensTable['fields']['clientID'],
			$this->config->accessTokensTable['fields']['userID'],
			$this->config->accessTokensTable['fields']['scope']
		));

		$success = $stmt->execute(array(
			':clientID' => $clientID,
			':userID' => $userID,
			':scope' => $scope
		));
		
		// TODO: error on db fail
		if (!$success) {
			die('not success: '.implode(' ', $stmt->errorInfo()));
		}
		
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

		if (count($results) === 0) {
			return NULL;
		}
		
		function sortByExpiresDescending($a, $b) {
			$expiresFieldName = $this->config->accessCodesTable['fields']['expires'];

			if ($a[$expiresFieldName] == $b[$expiresFieldName]) {
				return 0;
			}

			return $a[$expiresFieldName] > $b[$expiresFieldName] ? -1 : 1;
		}
		
		// sort by longest-lasting first
		uasort($results, 'sortByExpiresDescending');
		
		// pick first one and delete others
		$accessTokenFieldName = $this->config->accessTokensTable['fields']['accessToken'];
		$expiresFieldName = $this->config->accessTokensTable['fields']['expires'];
		
		$accessToken = $results[0][$accessTokenFieldName];
		$expires = $results[0][$expiresFieldName];
		
		if ($expires < time()) {
			$this->deleteAccessToken($accessToken);
			return NULL;
		}
				
		return new OAuth2AccessTokenObject(
			$accessToken,
			$clientID,
			$expires,
			OAuth2AccessTokenObject::TYPE_BEARER,
			$userID,
			$scope
		);
	}
		
	/**
	 * @param OAuth2AccessTokenObject $accessTokenObject
	 * 
	 * @return bool
	 */
	protected function saveAccessTokenObject(OAuth2AccessTokenObject $accessTokenObject) {
		$stmt = $this->config->pdo->prepare(sprintf(
			'INSERT INTO %s (%s, %s, %s, %s, %s) VALUES (:clientID, :accessToken, :expires, :userID, :scope)',
			$this->config->accessTokensTable['name'],
			$this->config->accessTokensTable['fields']['clientID'],
			$this->config->accessTokensTable['fields']['accessToken'],
			$this->config->accessTokensTable['fields']['expires'],
			$this->config->accessTokensTable['fields']['userID'],
			$this->config->accessTokensTable['fields']['scope']
		));
		
		$success = $stmt->execute(array(
			':clientID' => $accessTokenObject->clientID,
			':accessToken' => $accessTokenObject->accessToken,
			':expires' => $accessTokenObject->expires,
			':userID' => $accessTokenObject->userID,
			':scope' => $accessTokenObject->scope,
		));
		
		if (!$success) {
			// TODO: error on fail db
			die('failed saving access token object: '.implode(' ', $stmt->errorInfo()));
		}
	}
	
	/**
	 * @param int $credentialLocations (e.g. CredentialLocation::HEADER | CredentialLocation::BODY)
	 * 
	 * @return array
	 */
	protected function getClientCredentials($credentialLocations = CredentialLocation::HEADER) {
		$inHeader = isset($_SERVER['PHP_AUTH_USER']) + isset($_SERVER['PHP_AUTH_PW']);
		$inBody = isset($_POST['client_id']) + isset($_POST['client_secret']);
		$inURL = isset($_GET['client_id']) + isset($_GET['client_secret']);
		
		$numLocations = 0;
		
		if ($inHeader) {
			$numLocations++;
		}
		if ($inBody) {
			$numLocations++;
		}
		if ($inURL) {
			$numLocations++;
		}
		
		if ($numLocations === 0) {
			$error = new OAuth2Error(OAuth2Error::INVALID_CLIENT, 'no client credentials were specified');
			$this->outputError($error);
		}
		else if ($numLocations > 1) {
			$error = new OAuth2Error(OAuth2Error::INVALID_CLIENT, 'client credentials were specified in multiple locations');
			$this->outputError($error);
		}
		
		$isValidLocation = FALSE;
		
		// check specified locations in order of most secure
		if ($credentialLocations & CredentialLocation::HEADER) {
			$isValidLocation = TRUE;
			
			$bothInHeader = $inHeader == 2; // clientID and clientSecret are set
			if ($bothInHeader) {
				$clientID = $_SERVER['PHP_AUTH_USER'];
				$clientSecret = $_SERVER['PHP_AUTH_PW'];
			}
		}
		if ($credentialLocations & CredentialLocation::BODY) {
			$isValidLocation = TRUE;

			if (!isset($clientID) || !isset($clientSecret)) {
				$bothInBody = $inBody == 2;
				if ($bothInBody) { // clientID and clienSecret are set
					$clientID = $_POST['client_id'];
					$clientSecret = $_POST['client_secret'];
				}
			}
		}
	    if ($credentialLocations & CredentialLocation::URL) {
		    $isValidLocation = TRUE;

		    if (!isset($clientID) || !isset($clientSecret)) {
			    $bothInURL = $inURL == 2;
			    if ($bothInURL) { // clientID and clienSecret are set
				    $clientID = $_GET['client_id'];
				    $clientSecret = $_GET['client_secret'];
			    }
		    }
	    }
		
		if (!$isValidLocation) {
			// TODO: invalid credential location passed
			die('Invalid credential location: '.$credentialLocations);
		}
		
		if (!isset($clientID) || !isset($clientSecret)) { // means client credentials were defined in a location we don't want
			$locationDescriptions = CredentialLocation::descriptions($credentialLocations);
			$error = new OAuth2Error(OAuth2Error::INVALID_CLIENT, 'client credentials must be specified in one of the following locations: '.implode(', ', $locationDescriptions));
			$this->outputError($error);
		}
		
		return array(
			'client_id' => $clientID,
			'client_secret' => $clientSecret
		);
	}

	/**
	 * @param string $parameter
	 * @param int $parameterLocations (e.g. ParameterLocation::BODY | ParameterLocation::URL)
	 *
	 * @return string
	 */
	protected function getParameterValue($parameter, $parameterLocations = ParameterLocation::URL) {
		$inBody = isset($_POST[$parameter]);
		$inURL = isset($_GET[$parameter]);
				
		$numParameterLocations = intval($inBody) + intval($inURL);
		
		if ($numParameterLocations === 0) {
			return NULL;
		}
		else if ($numParameterLocations > 1) {
			$error = new OAuth2Error(OAuth2Error::INVALID_REQUEST, "parameters can only be defined in one location ({$parameter})");
			$this->outputError($error);
		}
		
		$isValidLocation = FALSE;
		
		if ($parameterLocations & ParameterLocation::BODY) {
			$isValidLocation = TRUE;
			$value = $inBody ? $_POST[$parameter] : NULL;
		}
		if ($parameterLocations & ParameterLocation::URL) {
			$isValidLocation = TRUE;
			if (!isset($value)) {
				$value = $inURL ? $_GET[$parameter] : NULL;
			}
		}
		
		if (!$isValidLocation) {
			// TODO: invalid parameter location passed
			die('Invalid parameter locations: '.$parameterLocations);
		}

		if (!isset($value)) { // means parameter was defined in a location we don't want
			$locationDescriptions = ParameterLocation::descriptions($parameterLocations);
			$error = new OAuth2Error(OAuth2Error::INVALID_REQUEST, "{$parameter} must be defined in one of the following locations: ".implode(', ', $locationDescriptions));
			$this->outputError($error);
		}
		
		return $value;
	}

	/**
	 * Retrieves the access token from the header if it exists
	 * @param int $accessTokenLocations (.e.g. AccessTokenLocation::HEADER | AccessTokenLocation::BODY)
	 * 
	 * @return string|NULL
	 */
	protected function getAccessToken($accessTokenLocations = AccessTokenLocation::HEADER_BEARER) {
		$token = null;
		$headers = function_exists('getallheaders') ? getallheaders() : apache_request_headers();
		if (isset($headers['Authorization'])){
			$matches = array();
			preg_match('/Bearer (.*)/', $headers['Authorization'], $matches);
			if (isset($matches[1])){
				$headerBearerToken = base64_decode($matches[1]);
			}
		}
		
		$inHeaderMac = FALSE; // TODO: header access token (mac)
		$inHeaderBearer = isset($headerBearerToken);
		$inBody = isset($_POST['access_token']);
		$inURL = isset($_GET['access_token']);

		$numLocations = intval($inHeaderMac) + intval($inHeaderBearer) + intval($inBody) + intval($inURL);

		if ($numLocations === 0) {
			$error = new OAuth2Error(OAuth2Error::INVALID_GRANT, 'no access token was defined');
			$this->outputError($error);
		}
		else if ($numLocations > 1) {
			$error = new OAuth2Error(OAuth2Error::INVALID_GRANT, 'access token was defined in multiple locations');
			$this->outputError($error);
		}
		
		$isValidLocation = FALSE;

		// check specified locations in order of most secure
		if ($accessTokenLocations & AccessTokenLocation::HEADER_MAC) {
			$isValidLocation = TRUE;
			$accessToken = NULL; // TODO: header access token (mac)
		}
		if ($accessTokenLocations & AccessTokenLocation::HEADER_BEARER) {
			$isValidLocation = TRUE;
			
			if (!isset($accessToken)) {
				$accessToken = $inHeaderBearer ? $headerBearerToken : NULL;
			}
		}
		if ($accessTokenLocations & AccessTokenLocation::BODY) {
			$isValidLocation = TRUE;
			
			if (!isset($accessToken)) {
				$accessToken = $inBody ? $_POST['access_token'] : NULL;
			}
		}
		if ($accessTokenLocations & AccessTokenLocation::URL) {
			$isValidLocation = TRUE;
			
			if (!isset($accessToken)) {
				$accessToken = $inURL ? $_GET['access_token'] : NULL;
			}
		}
		
		if (!$isValidLocation) {
			// TODO: invalid access token location passed
			die('Invalid access token location: '.$accessTokenLocations);
		}

		if (!isset($accessToken)) { // means access token was defined in a location we don't want
			$locationDescriptions = AccessTokenLocation::descriptions($accessTokenLocations);
			$error = new OAuth2Error(OAuth2Error::INVALID_GRANT, 'access token must be defined in one of the following locations: '.implode(', ', $locationDescriptions));
			$this->outputError($error);
		}

		return $accessToken;
	}

	
	/**
	 * @param string $clientID
	 * @param string $clientSecret
	 * 
	 * @return bool
	 */
	protected function verifyClientCredentials($clientID, $clientSecret) {
		// check db for client id and secret match
		$stmt = $this->config->pdo->prepare(sprintf(
			'SELECT COUNT(1) AS Count FROM %s WHERE %s = :id && %s = :secret',
			$this->config->clientsTable['name'],
			$this->config->clientsTable['fields']['id'],
			$this->config->clientsTable['fields']['secret']
		));
		
		$success = $stmt->execute(array(
			':id' => $clientID,
			':secret' => $this->hashSecret($clientSecret, $clientID)
		));

		if (!$success) {
			// TODO: error for db failure
			die('verify client credentials db fail');
		}

		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		return intval($results[0]['Count']) === 1;
	}

	/**
	 * @param string $accessToken
	 * @param string $scope
	 *
	 * @return bool
	 */
	protected function verifyAccessToken($accessToken, $scope = '') {
		// check db for client id and secret match
		$stmt = $this->config->pdo->prepare(sprintf(
			'SELECT %s FROM %s WHERE %s = :accessToken AND %s LIKE :scope',
			$this->config->accessTokensTable['fields']['expires'],
			$this->config->accessTokensTable['name'],
			$this->config->accessTokensTable['fields']['accessToken'],
			$this->config->accessTokensTable['fields']['scope']
		));
		
		$success = $stmt->execute(array(
			':accessToken' => $accessToken,
			':scope' => $scope
		));
		
		if (!$success) {
			// TODO: error for db failure
			die('verify access token db fail');
		}
		
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

		if (count($results) === 0) {
			return FALSE;
		}

		$expires = $results[0][$this->config->accessTokensTable['fields']['expires']];
		if ($expires < time()) { // is expired
			$this->deleteAccessToken($accessToken);

			return FALSE;
		}
		
		return TRUE;
	}

	/**
	 * @param string $accessToken
	 *
	 * @return bool
	 */
	protected function deleteAccessToken($accessToken) {
		// check db for client id and secret match
		$stmt = $this->config->pdo->prepare(sprintf(
			'DELETE FROM %s WHERE %s = :accessToken',
			$this->config->accessTokensTable['name'],
			$this->config->accessTokensTable['fields']['accessToken']
		));

		$success = $stmt->execute(array(
			':accessToken' => $accessToken
		));

		if (!$success) {
			// TODO: error for db failure
			die('delete access token db fail');
		}
	}
}