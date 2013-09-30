<?php
/**
 * Created by Brad Walker on 5/15/13 at 7:01 PM
*/

/**
 * Conforms to OAuth 2.0 Draft 31
 * 
 * Class OAuth2GrantType
 */
class OAuth2GrantType {
	const AUTHORIZATION_CODE = 'authorization_code';
	const RESOURCE_OWNER_PASSWORD_CREDENTIALS = 'password';
	const CLIENT_CREDENTIALS = 'client_credentials';
	
	const REFRESH_TOKEN = 'refresh_token';

	private function __construct() {}
}