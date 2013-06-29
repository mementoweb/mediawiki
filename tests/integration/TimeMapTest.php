<?php
require_once("HTTPFetch.php");
require_once("MementoParse.php");
require_once("TestSupport.php");
require_once('PHPUnit/Extensions/TestDecorator.php');

error_reporting(E_ALL | E_NOTICE | E_STRICT);

$HOST = $_ENV["TESTHOST"];
$DEBUG = false;

class TimeMapTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider acquireTimeMap404Urls
	 */
	public function test404TimeMap($TIMEMAP) {
	
		global $HOST;

        $request = "GET $TIMEMAP HTTP/1.1\r\n";
        $request .= "Host: $HOST\r\n";
        $request .= "Connection: close\r\n\r\n";

		$response = HTTPFetch($HOST, 80, $request);

		$statusline = extractStatusLineFromResponse($response);

        $this->assertEquals($statusline["code"], "404");
	}

	/**
	 * @dataProvider acquireTimeMapTestData
	 */
	public function testGetTimeMap(
		$TIMEMAP,
		$EXPECTEDFILE
		) {

        global $HOST;

        $request = "GET $TIMEMAP HTTP/1.1\r\n";
        $request .= "Host: $HOST\r\n";
        $request .= "Connection: close\r\n\r\n";

		$response = HTTPFetch($HOST, 80, $request);

		$expectedOutput = file_get_contents($EXPECTEDFILE);

		$entity = extractEntityFromResponse($response);

		$this->assertEquals($expectedOutput, $entity);
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
