<?php

require_once('MementoConfig.php');

$wgArticlePath = null;
$wgServer = null;
$wgMementoExcludeNamespaces = null;
$wgMementoTimeMapNumberOfMementos = null;
$wgMementoErrorPageType = null;
$wgMementoPattern = null;

class ConfigurationTest extends PHPUnit_Framework_TestCase {

	protected function setUp() {

		global $wgArticlePath;
		global $wgServer;
		global $wgMementoExcludeNamespaces;
		global $wgMementoTimeMapNumberOfMementos;
		global $wgMementoErrorPageType;
		global $wgMementoPattern;

		$this->expectedArticlePath = "/somepath/somewhere/";
		$this->expectedServer = "http://localhost";
		$this->expectedExcludeNamespaces = "notsurehere";
		$this->expectedNumberOfMementos = 20;
		$this->expectedErrorPageType = "traditional";
		$this->expectedPattern = "separate";
		
		$wgArticlePath = $this->expectedArticlePath;
		$wgServer = $this->expectedServer;
		$wgMementoExcludeNamespaces = $this->expectedExcludeNamespaces;
		$wgMementoTimeMapNumberOfMementos = 
			$this->expectedNumberOfMementos;
		$wgMementoErrorPageType = $this->expectedErrorPageType;
		$wgMementoPattern = $this->expectedPattern;


	}

	public function testConfiguration() {

		$config = new MementoConfig();

		$this->assertEquals(
			$this->expectedArticlePath, $config->get('ArticlePath'));

		$this->assertEquals($this->expectedServer, $config->get('Server'));

		$this->assertEquals(
			$this->expectedExcludeNamespaces,
			$config->get('ExcludedNamespaces'));

		$this->assertEquals(
			$this->expectedNumberOfMementos,
			$config->get('NumberOfMementos'));

		$this->assertEquals(
			$this->expectedErrorPageType, $config->get('ErrorPageType'));

		$this->assertEquals(
			$this->expectedPattern, $config->get('Pattern'));

	}

	public function testConfigurationDefaults() {

		unset($GLOBALS['wgArticlePath']);

		// this really should be set by the Mediawiki install
		//unset($GLOBALS['wgServer']);

		unset($GLOBALS['wgMementoExcludeNamespaces']);
		unset($GLOBALS['wgMementoTimeMapNumberOfMementos']);
		unset($GLOBALS['wgMementoErrorPageType']);

		$config = new MementoConfig();

		$this->assertEquals(
			"/index.php/$1", $config->get('ArticlePath'));

		$this->assertEquals(null, $config->get('ExcludedNamespaces'));

		$this->assertEquals(500, $config->get('NumberOfMementos'));

		$this->assertEquals(
			'traditional', $config->get('ErrorPageType'));

		$this->assertEquals(
			'separate', $config->get('Pattern'));

	}

}

?>
