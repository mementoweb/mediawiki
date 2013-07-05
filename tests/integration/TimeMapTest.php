<?php
require_once("HTTPFetch.php");
require_once("MementoParse.php");
require_once("TestSupport.php");
require_once('PHPUnit/Extensions/TestDecorator.php');

error_reporting(E_ALL | E_NOTICE | E_STRICT);

$HOST = $_ENV["TESTHOST"];
$TMDEBUG = false;

class TimeMapTest extends PHPUnit_Framework_TestCase {

	/**
	 * @group traditionalErrorPages
     * 
	 * @dataProvider acquireTimeMap404Urls
	 */
	public function test404TimeMap($TIMEMAP) {
	
		global $HOST;
		global $TMDEBUG;

        $request = "GET $TIMEMAP HTTP/1.1\r\n";
        $request .= "Host: $HOST\r\n";
        $request .= "Connection: close\r\n\r\n";

		$response = HTTPFetch($HOST, 80, $request);

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
	
		global $HOST;
		global $TMDEBUG;

        $request = "GET $TIMEMAP HTTP/1.1\r\n";
        $request .= "Host: $HOST\r\n";
        $request .= "Connection: close\r\n\r\n";

		$response = HTTPFetch($HOST, 80, $request);

		if ($TMDEBUG) {
			echo "\n";
			echo $response . "\n";
			echo "\n";
		}

		$statusline = extractStatusLineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        $this->assertEquals($statusline["code"], "200");

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
	public function testGetTimeMap(
		$TIMEMAP,
		$EXPECTEDFILE
		) {

        global $HOST;
		global $TMDEBUG;

        $request = "GET $TIMEMAP HTTP/1.1\r\n";
        $request .= "Host: $HOST\r\n";
        $request .= "Connection: close\r\n\r\n";

		$response = HTTPFetch($HOST, 80, $request);

		if ($TMDEBUG) {
			echo "\n";
			echo $response . "\n";
			echo "\n";
		}

		$expectedOutput = file_get_contents($EXPECTEDFILE);

		$entity = extractEntityFromResponse($response);

		# Note that this test may assume $wgMementoTimemapNumberOfMementos = 3
		$this->assertEquals($expectedOutput, $entity);

		# To catch any PHP errors that the test didn't notice
		$this->assertNotContains("Fatal error", $entity);
	}

	public function acquireTimeMapTestData() {
		return acquireCSVDataFromFile(
			"tests/integration/test-data/timemap-testdata.csv", 2);
	}

	public function acquireTimeMap404Urls() {
		return acquireLinesFromFile(
			"tests/integration/test-data/timemap404-testdata.csv");
	}
}
