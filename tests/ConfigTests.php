<?php
/**
 * Created by Brad Walker on 11/9/13 at 5:15 PM
*/

require_once('../vendor/autoload.php');

/**
 * Class ConfigTest
 */
class ConfigTest extends PHPUnit_Framework_TestCase {
	function testInitFromFile() {
		$config = \FREST\Config::fromFile('resources/test-config.json');
		
		$this->assertEquals('test-type', $config->getConfigArray()['db']['type']);
		$this->assertEquals('test-host', $config->getConfigArray()['db']['host']);
		$this->assertEquals('test-name', $config->getConfigArray()['db']['name']);
		$this->assertEquals('test-username', $config->getConfigArray()['db']['username']);
		$this->assertEquals('test-password', $config->getConfigArray()['db']['password']);
		
		$this->assertFalse($config->getSuppressHTTPStatusCodes());
		$this->assertTrue($config->getShowDiagnostics());
		$this->assertTrue($config->getCheckResourceValidity());
		$this->assertFalse($config->getEnableForcedMethod());
		$this->assertEquals("test-resource-directory", $config->getResourceDirectory());
	}
	
	function testInitFromFileWithoutDB() {
		$this->setExpectedException('\FREST\Exception', NULL, \FREST\Exception::Config);
		\FREST\Config::fromFile('resources/test-config-no-db.json');
	}
} 