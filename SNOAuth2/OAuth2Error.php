<?php
/**
 * Created by Brad Walker on 5/15/13 at 7:23 PM
*/

/**
 * Conforms to OAuth 2.0 Draft 31
 * 
 * @property string $errorMessage
 * @property string $description
 * @property OAuth2Error $uri
 * 
 * Class OAuthError
 */
class OAuth2Error {
	
	/**
	 * The request is missing a required parameter, includes an
	 * unsupported parameter value (other than grant type),
	 * repeats a parameter, includes multiple credentials,
	 * utilizes more than one mechanism for authenticating the
	 * client, or is otherwise malformed.
	 */
	const INVALID_REQUEST = 'invalid_request';

	/**
	 * Client authentication failed (e.g. unknown client, no
	 * client authentication included, or unsupported
	 * authentication method).
	 */
	const INVALID_CLIENT = 'invalid_client';

	/**
	 * The provided authorization grant (e.g. authorization
	 * code, resource owner credentials) or refresh token is
	 * invalid, expired, revoked, does not match the redirection
	 * URI used in the authorization request, or was issued to
	 * another client.
	 */
	const INVALID_GRANT = 'invalid_grant';

	/**
	 * The authenticated client is not authorized to use this
	 * authorization grant type.
	 */
	const UNAUTHORIZED_CLIENT = 'unauthorized_client';
	
	/**
	 * The authorization grant type is not supported by the
	 * authorization server.
	 */
	const UNSUPPORTED_GRANT_TYPE = 'unsupported_grant_type';

	/**
	 * The requested scope is invalid, unknown, malformed, or
	 * exceeds the scope granted by the resource owner.
	 */
	const INVALID_SCOPE = 'invalid_scope';

	
	protected static $descriptions = array(
		self::INVALID_REQUEST => 'The request is missing a required parameter, includes an unsupported parameter value (other than grant type), repeats a parameter, includes multiple credentials, utilizes more than one mechanism for authenticating the client, or is otherwise malformed.',
		self::INVALID_CLIENT => 'Client authentication failed (e.g. unknown client, no client authentication included, or unsupported authentication method)',
		self::INVALID_GRANT => 'The provided authorization grant (e.g. authorization code, resource owner credentials) or refresh token is invalid, expired, revoked, does not match the redirection URI used in the authorization request, or was issued to another client.',
		self::UNAUTHORIZED_CLIENT => 'The authenticated client is not authorized to use this authorization grant type.',
		self::UNSUPPORTED_GRANT_TYPE => 'The authorization grant type is not supported by the authorization server.',
		self::INVALID_SCOPE => 'The requested scope is invalid, unknown, malformed, or exceeds the scope granted by the resource owner.'
	);
	
	
	
	
	

	protected $error;
	protected $description;
	protected $uri;


	public function __construct($error, $description = NULL, $uri = NULL) {
		switch ($error) {
			case self::INVALID_REQUEST:
			case self::INVALID_CLIENT:
			case self::INVALID_GRANT:
			case self::UNAUTHORIZED_CLIENT:
			case self::UNSUPPORTED_GRANT_TYPE:
			case self::INVALID_SCOPE:
				$this->error = $error;
				$this->description = isset($description) ? $description : self::$descriptions[$error];
				$this->uri = isset($uri) ? $uri : 'http://tools.ietf.org/html/draft-ietf-oauth-v2-31#section-5.2';
				break;
			default:
				die('ERROR: Invalid error supplied to OAuthError constructor');
				break;
		}
	}
	
	
	
	
	
	
	/**
	 * @return string
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @return string
	 */
	public function getURI() {
		return $this->uri;
	}

	/**
	 * @param bool $withDescription
	 * @param bool $withURI
	 * @return mixed
	 */
	public function getResponse($withDescription = TRUE, $withURI = TRUE) {
		$response['error'] = $this->error;
		
		if ($withDescription && isset($this->description)) {
			$response['error_description'] = $this->description;
		}

		if ($withURI && isset($this->uri)) {
			$response['error_uri'] = $this->uri;
		}
		
		return $response;
	}
}