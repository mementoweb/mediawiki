<?php

require_once('MementoConfig.php');

$wgArticlePath = null;
$wgServer = null;
$wgMementoExcludeNamespaces = null;
$wgMementoTimemapNumberOfMementos = null;
$wgMementoErrorPageType = null;
$wgMementoRecommendedRelations = null;

class ConfigurationTest extends PHPUnit_Framework_TestCase {

	protected function setUp() {

		global $wgArticlePath;
		global $wgServer;
		global $wgMementoExcludeNamespaces;
		global $wgMementoTimemapNumberOfMementos;
		global $wgMementoErrorPageType;
		global $wgMementoRecommendedRelations;


		$this->expectedArticlePath = "/somepath/somewhere/";
		$this->expectedServer = "http://localhost";
		$this->expectedExcludeNamespaces = "notsurehere";
		$this->expectedNumberOfMementos = 20;
		$this->expectedErrorPageType = "traditional";
		$this->expectedRecommendedRelations = true;
		
		$wgArticlePath = $this->expectedArticlePath;
		$wgServer = $this->expectedServer;
		$wgMementoExcludeNamespaces = $this->expectedExcludeNamespaces;
		$wgMementoTimemapNumberOfMementos = 
			$this->expectedNumberOfMementos;
		$wgMementoErrorPageType = $this->expectedErrorPageType;
		$wgMementoRecommendedRelations = true;


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
			$this->expectedRecommendedRelations, $config->get('RecommendedRelations'));


	}

	public function testConfigurationDefaults() {

		unset($GLOBALS['wgArticlePath']);

		// this really should be set by the Mediawiki install
		//unset($GLOBALS['wgServer']);

		unset($GLOBALS['wgMementoExcludeNamespaces']);
		unset($GLOBALS['wgMementoTimemapNumberOfMementos']);
		unset($GLOBALS['wgMementoErrorPageType']);
		unset($GLOBALS['wgMementoRecommendedRelations']);

		$config = new MementoConfig();

		$this->assertEquals(
			"/index.php/$1", $config->get('ArticlePath'));

		$this->assertEquals(null, $config->get('ExcludedNamespaces'));

		$this->assertEquals(500, $config->get('NumberOfMementos'));

		$this->assertEquals(
			'friendly', $config->get('ErrorPageType'));


	}

}

?>
