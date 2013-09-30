<?php
/**
 * Created by Brad Walker on 5/15/13 at 7:04 PM
*/

require_once('SNOAuth2DelegateInterface.php');
require_once('SNOAuth2Config.php');
require_once('OAuth2Error.php');
require_once('OAuth2AccessTokenObject.php');
require_once('OAuth2GrantType.php');



if( !function_exists('apache_request_headers') ) {
	function apache_request_headers() {
		$arh = array();
		$rx_http = '/\AHTTP_/';
		foreach($_SERVER as $key => $val) {
			if( preg_match($rx_http, $key) ) {
				$arh_key = preg_replace($rx_http, '', $key);
				$rx_matches = array();
				// do some nasty string manipulations to restore the original letter case
				// this should work in most cases
				$rx_matches = explode('_', $arh_key);
				if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
					foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
					$arh_key = implode('-', $rx_matches);
				}
				$arh[$arh_key] = $val;
			}
		}
		return( $arh );
	}
}



/**
 * Conforms to OAuth 2.0 Draft 31
 * 
 * @property SNOAuth2Config $config
 * 
 * Class OAuth2_31
 */
class SNOAuth2 {

	const HASH_SALT = 'axv87twregl2rblkjp';
	
	protected $config;
	
	/**
	 * @param SNOAuth2Config $config
	 */
	public function __construct(SNOAuth2Config $config) {
		$this->config = $config;
		
		return $this;
	}


	/**
	 * @param string $clientID
	 * @param string $clientSecret
	 * @param string $redirectURI
	 * 
	 * @return bool
	 */
	public function addClient($clientID, $clientSecret, $redirectURI) {
		$stmt = $this->config->pdo->prepare(sprintf(
			'INSERT INTO %s (%s, %s, %s) VALUES (:id, :secret, :redirectURI)',
			$this->config->clientsTable['name'],
			$this->config->clientsTable['fields']['id'],
			$this->config->clientsTable['fields']['secret'],
			$this->config->clientsTable['fields']['redirectURI']
		));
		
		$success = $stmt->execute(array(
			':id' => $clientID,
			':secret' => $this->hashSecret($clientSecret, $clientID),
			':redirectURI' => $redirectURI
		));
		
		return $success;
	}
	 
	/**
	 * @param array $responseArray
	 */
	protected function outputResponse($responseArray) {
		if (!headers_sent() && php_sapi_name() !== 'cli') {
			header( "{$_SERVER['SERVER_PROTOCOL']} 200 OK", TRUE, 200);
			header('Content-Type: application/json');
			header('Cache-Control: no-store');
			header('Pragma: no-cache');
		}
		
		die(json_encode($responseArray));
	}

	/**
	 * @param OAuth2Error $error
	 */
	protected function outputError(OAuth2Error $error) {
		$response = $error->getResponse($this->config->showErrorDescriptions, $this->config->showErrorURIs);
		$this->outputResponse($response);
	}

	/**
	 * @param OAuth2AccessTokenObject $accessToken
	 */
	protected function outputAccessTokenObject(OAuth2AccessTokenObject $accessToken) {
		$response = $accessToken->getResponse();
		$this->outputResponse($response);
	}

	/**
	 * @param array $array
	 * 
	 * @return int
	 */
	protected function bitwiseORArray($array) {
		$combined = array_reduce($array,
			function($a, $b) {
				return $a | $b;
			},
			0
		);
		
		return $combined;
	}
	
	/**
	 * @param string $clientSecret
	 * @param string $clientID
	 * @return string
	 */
	protected function hashSecret($clientSecret, $clientID = NULL) {
		if ($this->config->delegate && ($this->config->delegate instanceof SNOAuth2DelegateInterface)) {
			return $this->config->delegate->hashSNOAuth2Secret($clientSecret, $clientID);
		}
		else {
			return hash_hmac('ripemd160', "{$clientSecret}:{$clientID}", self::HASH_SALT);
		}
	}
	
	protected function generateAccessToken() {
		return hash_hmac('ripemd160', uniqid('', TRUE), self::HASH_SALT);
	}
}


final class CredentialLocation {
	const HEADER = 4;
	const BODY = 2;
	const URL = 1;

	private static $descriptions = array(
		self::HEADER => 'header',
		self::BODY => 'body',
		self::URL => 'url'
	);

	/**
	 * @param int $locations
	 * @return array
	 */
	public static function descriptions($locations) {
		$descriptions = array();
		
		if ($locations & self::HEADER) {
			$descriptions[] = self::$descriptions[self::HEADER];
		}
		if ($locations & self::BODY) {
			$descriptions[] = self::$descriptions[self::BODY];
		}
		if ($locations & self::URL) {
			$descriptions[] = self::$descriptions[self::URL];
		}
		
		return $descriptions;
	}
}

final class ParameterLocation {
	const BODY = 2;
	const URL = 1;

	private static $descriptions = array(
		self::BODY => 'body',
		self::URL => 'url'
	);

	/**
	 * @param int $locations
	 * @return array
	 */
	public static function descriptions($locations) {
		$descriptions = array();

		if ($locations & self::BODY) {
			$descriptions[] = self::$descriptions[self::BODY];
		}
		if ($locations & self::URL) {
			$descriptions[] = self::$descriptions[self::URL];
		}

		return $descriptions;
	}
}

final class AccessTokenLocation {
	const HEADER_MAC = 8;
	const HEADER_BEARER = 4;
	const BODY = 2;
	const URL = 1; // passing access tokens in the URL is highly discouraged in OAuth 2.0 draft 31

	private static $descriptions = array(
		self::HEADER_MAC => 'header:mac',
		self::HEADER_BEARER => 'header:bearer',
		self::BODY => 'body)',
		self::URL => 'url'
	);

	/**
	 * @param int $locations
	 * @return array
	 */
	public static function descriptions($locations) {
		$descriptions = array();

		if ($locations & self::HEADER_MAC) {
			$descriptions[] = self::$descriptions[self::HEADER_MAC];
		}
		if ($locations & self::HEADER_BEARER) {
			$descriptions[] = self::$descriptions[self::HEADER_BEARER];
		}
		if ($locations & self::BODY) {
			$descriptions[] = self::$descriptions[self::BODY];
		}
		if ($locations & self::URL) {
			$descriptions[] = self::$descriptions[self::URL];
		}

		return $descriptions;
	}
}