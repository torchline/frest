<?php
/**
 * Created by Brad Walker on 10/2/13 at 12:15 AM
*/

require_once('../FREST/FREST.php');

class UsersTest extends PHPUnit_Framework_TestCase {
			
	function testSingularRead() {
		$frest = FREST::singular(1, 'users', array(), FRMethod::GET);
		$resultObject = $frest->outputResult(FROutputFormat::OBJECT);
		
		$this->assertSuccessfulResult($resultObject);
		$this->assertValidSingularReadResult($resultObject);
		
		$user = $resultObject->response;

		$this->assertCompleteUser($user);
	}

	function testSingularPartialRead() {
		$frest = FREST::singular(1, 'users', array('fields' => 'name,token'), FRMethod::GET);
		$resultObject = $frest->outputResult(FROutputFormat::OBJECT);

		$this->assertSuccessfulResult($resultObject);
		$this->assertValidSingularReadResult($resultObject);

		$user = $resultObject->response;

		$this->assertEquals(2, count(get_object_vars($user)));

		$this->assertObjectNotHasAttribute('id', $user);
		$this->assertObjectNotHasAttribute('rank', $user);
		$this->assertEquals('AccessToken1', $user->token);
		$this->assertEquals('Name1', $user->name);		
		$this->assertObjectNotHasAttribute('modified', $user);
	}

	function testSingularPartialCompleteRead() {
		$frest = FREST::singular(1, 'users', array('fields' => 'id,name,token,modified,rank'), FRMethod::GET);
		$resultObject = $frest->outputResult(FROutputFormat::OBJECT);

		$this->assertSuccessfulResult($resultObject);
		$this->assertValidSingularReadResult($resultObject);

		$user = $resultObject->response;

		$this->assertCompleteUser($user);
	}

	function testPluralRead() {
		$frest = FREST::plural('users', array(), FRMethod::GET);
		$resultObject = $frest->outputResult(FROutputFormat::OBJECT);

		$this->assertSuccessfulResult($resultObject);
		$this->assertValidPluralReadResult($resultObject);

		$users = $resultObject->response;

		$this->assertCount(4, $users);

		foreach ($users as $user) {
			$this->assertCompleteUser($user);
		}
	}

	function testPluralPartialRead() {
		$frest = FREST::plural('users', array('fields' => 'rank,name'), FRMethod::GET);
		$resultObject = $frest->outputResult(FROutputFormat::OBJECT);

		$this->assertSuccessfulResult($resultObject);
		$this->assertValidPluralReadResult($resultObject);

		$users = $resultObject->response;

		$this->assertCount(4, $users);

		foreach ($users as $user) {
			$this->assertEquals(2, count(get_object_vars($user)));
			
			$this->assertObjectNotHasAttribute('id', $user);
			$this->assertObjectNotHasAttribute('token', $user);
			$this->assertStringStartsWith('Name', $user->name);
			$this->assertObjectHasAttribute('rank', $user);
			$this->assertInstanceOf('stdClass', $user->rank);
			$this->assertObjectNotHasAttribute('modified', $user);
		}
	}

	function testPluralPartialCompleteRead() {
		$frest = FREST::plural('users', array('fields' => 'token,rank,modified,id,name'), FRMethod::GET);
		$resultObject = $frest->outputResult(FROutputFormat::OBJECT);

		$this->assertSuccessfulResult($resultObject);
		$this->assertValidPluralReadResult($resultObject);

		$users = $resultObject->response;

		$this->assertCount(4, $users);

		foreach ($users as $user) {
			$this->assertCompleteUser($user);
		}
	}






	private function assertCompleteUser($user) {
		$this->assertEquals(5, count(get_object_vars($user)));
		
		$this->assertObjectHasAttribute('id', $user);
		$id = $user->id;
		$this->assertGreaterThan(0, $id);

		$this->assertEquals("AccessToken{$id}", $user->token);
		$this->assertEquals("Name{$id}", $user->name);
		
		$this->assertInstanceOf('stdClass', $user->rank);
		
		$rank = $user->rank;
		$this->assertObjectHasAttribute('id', $rank);
		$this->assertObjectHasAttribute('name', $rank);
		$this->assertObjectHasAttribute('ordinal', $rank);

		$this->assertEquals(1325379661, $user->modified);
	}
	
	private function assertSuccessfulResult($resultObject) {
		$this->assertObjectHasAttribute('status', $resultObject);
		$this->assertEquals(200, $resultObject->status);
	}
	
	private function assertValidSingularReadResult($resultObject) {
		$this->assertObjectHasAttribute('response', $resultObject);
		$this->assertInstanceOf('stdClass', $resultObject->response);
	}

	private function assertValidPluralReadResult($resultObject) {
		$this->assertObjectHasAttribute('response', $resultObject);
		$this->assertInternalType('array', $resultObject->response);

		$this->assertObjectHasAttribute('meta', $resultObject);
		$meta = $resultObject->meta;
		$this->assertInstanceOf('stdClass', $resultObject->meta);

		$this->assertObjectHasAttribute('count', $meta);
		$this->assertObjectHasAttribute('offset', $meta);
		$this->assertObjectHasAttribute('limit', $meta);
	}
}