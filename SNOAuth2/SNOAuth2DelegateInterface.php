<?php
/**
 * Created by Brad Walker on 5/15/13 at 9:07 PM
*/

/** 
 * Class SNOAuth2DelegateInterface
 */
interface SNOAuth2DelegateInterface {
	public function hashSNOAuth2Secret($clientSecret, $clientID);
}