<?php
require_once("HTTPFetch.php");
require_once("MementoParse.php");
require_once("TestSupport.php");
require_once('PHPUnit/Extensions/TestDecorator.php');

error_reporting(E_ALL | E_NOTICE | E_STRICT);

$TMDEBUG = false;

class TimeMapTest extends PHPUnit_Framework_TestCase {

	public static function setUpBeforeClass() {
		global $sessionCookieString;

		$sessionCookieString = authenticateWithMediawiki();
	}

	public static function tearDownAfterClass() {

		logOutOfMediawiki();
	}

	/**
	 * @group traditionalErrorPages
     * 
	 * @dataProvider acquireTimeMap404Urls
	 */
	public function test404TimeMap($TIMEMAP) {

		global $TMDEBUG;

		global $sessionCookieString;

		$uagent = "Memento-Mediawiki-Plugin/Test";

		$response = `curl -s -e '$uagent' -b '$sessionCookieString' -k -i --url '$TIMEMAP'`;

		if ($TMDEBUG) {
			echo "\n";
			echo $response . "\n";
			echo "\n";
		}

		$statusline = extractStatusLineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        $this->assertEquals($statusline["code"], "404");

		# To catch any PHP errors that the test didn't notice
		$this->assertNotContains("<b>Fatal error</b>", $entity);

		# To catch any PHP notices that the test didn't notice
		$this->assertNotContains("<b>Notice</b>", $entity);

		# To catch any PHP notices that the test didn't notice
		$this->assertNotContains("<b>Warning</b>", $entity);

		# To ensure that the error message actually exists in the output
		$expected = acquireFormattedI18NString('en', 'timemap-404-title');
		$this->assertStringMatchesFormat("$expected", $entity);
	}

	/**
	 * @group friendlyErrorPages
     * 
	 * @dataProvider acquireTimeMap404Urls
	 */
	public function testFriendlyErrorTimeMap($TIMEMAP) {
	
		global $sessionCookieString;

		global $TMDEBUG;

		$uagent = "Memento-Mediawiki-Plugin/Test";

		$response = `curl -s -e '$uagent' -b '$sessionCookieString' -k -i --url '$TIMEMAP'`;

		if ($TMDEBUG) {
			echo "\n";
			echo $response . "\n";
			echo "\n";
		}

		$statusline = extractStatusLineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        $this->assertEquals("200", $statusline["code"]);

		# To catch any PHP errors that the test didn't notice
		$this->assertNotContains("<b>Fatal error</b>", $entity);

		# To catch any PHP notices that the test didn't notice
		$this->assertNotContains("<b>Notice</b>", $entity);

		# To catch any PHP notices that the test didn't notice
		$this->assertNotContains("<b>Warning</b>", $entity);

		# To ensure that the error message actually exists in the output
		$expected = acquireFormattedI18NString('en', 'timemap-404-title');
		$this->assertStringMatchesFormat("%a" . $expected . "%a", $entity);
	}

	/**
	 * @group all
	 *
	 * @dataProvider acquireTimeMapTestData
	 */
	#public function testGetTimeMap(
	#	$TIMEMAP,
	#	$EXPECTEDFILE
	#	) {

	#	global $TMDEBUG;

	#	global $sessionCookieString;

	#	$uagent = "Memento-Mediawiki-Plugin/Test";

	#	$response = `curl -s -e '$uagent' -b '$sessionCookieString' -k -i --url '$TIMEMAP'`;


	#	if ($TMDEBUG) {
	#		echo "\n";
	#		echo $response . "\n";
	#		echo "\n";
	#	}

	#	$expectedOutput = file_get_contents($EXPECTEDFILE);

	#	$entity = extractEntityFromResponse($response);

	#	# Note that this test may assume $wgMementoTimemapNumberOfMementos = 3
	#	$this->assertEquals($expectedOutput, $entity);

	#	# To catch any PHP errors that the test didn't notice
	#	$this->assertNotContains("Fatal error", $entity);
	#}


	public function acquireTimeMapTestData() {
		return acquireCSVDataFromFile(
			getenv('TESTDATADIR') . '/timemap-testdata.csv', 2);
	}

	public function acquireTimeMap404Urls() {
		return acquireLinesFromFile(
			getenv('TESTDATADIR') . '/timemap-dneurls-testdata.csv');
	}
}
