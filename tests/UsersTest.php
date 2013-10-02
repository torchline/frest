<?php
/**
 * Created by Brad Walker on 10/2/13 at 12:15 AM
*/

require_once('../FREST/FREST.php');

class UsersTest extends PHPUnit_Framework_TestCase {
	
	protected $config;
		
	function testVanillaRead() {
		$frest = new FREST(NULL, 'users', 1, array(), FRMethod::GET);
		
		$jsonResultString = $frest->outputResult(FROutputFormat::JSON, TRUE);
		$jsonResult = json_decode($jsonResultString, TRUE);
		
		$this->assertEquals(200, $jsonResult['status']);
	}
}