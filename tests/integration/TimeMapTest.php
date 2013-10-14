<?php
require_once("HTTPFetch.php");
require_once("MementoParse.php");
require_once("TestSupport.php");
require_once('PHPUnit/Extensions/TestDecorator.php');

error_reporting(E_ALL | E_NOTICE | E_STRICT);

class TimeMapTest extends PHPUnit_Framework_TestCase {

	public static $instance = 0;

	public static function setUpBeforeClass() {
		global $sessionCookieString;

		$sessionCookieString = authenticateWithMediawiki();
	}

	public static function tearDownAfterClass() {

		logOutOfMediawiki();
	}

	protected function setUp() {
		self::$instance++;
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

		$outputfile = __CLASS__ . '.' . __FUNCTION__ . '.' . self::$instance . '.txt';
		$debugfile = __CLASS__ . '.' . __FUNCTION__ . '-debug-' . self::$instance . '.txt';

		$curlCmd = "curl -v -s -A '$uagent' -b '$sessionCookieString' -k -i --url '$TIMEMAP'";
		$response = `$curlCmd 2> $debugfile | tee "$outputfile"`;

		$statusline = extractStatusLineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        $this->assertEquals($statusline["code"], "404");

		# To catch any PHP errors that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Fatal error</b>"));

		# To catch any PHP notices that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Notice</b>"));

		# To catch any PHP notices that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Warning</b>"));

		# To ensure that the error message actually exists in the output
		$expected = acquireFormattedI18NString('en', 'timemap-404-title');
		$this->assertStringMatchesFormat("$expected", $entity);
	}

	/**
	 * @group friendlyErrorPages
     * 
	 * @dataProvider acquireTimeMap404Urls
	 */
	public function testFriendly404TimeMap($TIMEMAP) {
	
		global $sessionCookieString;

		global $TMDEBUG;

		$uagent = "Memento-Mediawiki-Plugin/Test";

		$outputfile = __CLASS__ . '.' . __FUNCTION__ . '.' . self::$instance . '.txt';
		$debugfile = __CLASS__ . '.' . __FUNCTION__ . '-debug-' . self::$instance . '.txt';

		$curlCmd = "curl -v -s -A '$uagent' -b '$sessionCookieString' -k -i --url '$TIMEMAP'";
		$response = `$curlCmd 2> $debugfile | tee "$outputfile"`;

		$statusline = extractStatusLineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        $this->assertEquals("200", $statusline["code"]);

		# To catch any PHP errors that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Fatal error</b>"));

		# To catch any PHP notices that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Notice</b>"));

		# To catch any PHP notices that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Warning</b>"));

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
		$IDENTIFIER,
		$TIMEMAP,
		$EXPECTEDFILE,
		$COMMENT
		) {

		global $TMDEBUG;

		global $sessionCookieString;

		$uagent = "Memento-Mediawiki-Plugin/Test";

		$outputfile = __CLASS__ . '.' . __FUNCTION__ . '.' . self::$instance . '.txt';
		$debugfile = __CLASS__ . '.' . __FUNCTION__ . '-debug-' . self::$instance . '.txt';

		$curlCmd = "curl -v -s -A '$uagent' -H \"X-TestComment: $COMMENT\" -b '$sessionCookieString' -k -i --url '$TIMEMAP'";
		$response = `$curlCmd 2> $debugfile | tee "$outputfile"`;

		# Note that this test is disabled until we can acquire test data
		#$expectedOutput = file_get_contents($EXPECTEDFILE);

		$entity = extractEntityFromResponse($response);

		# Note that this test is disabled until we can acquire test data
		#$this->assertEquals($expectedOutput, $entity);

		# To catch any PHP errors that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Fatal error</b>"));

		# To catch any PHP notices that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Notice</b>"));

		# To catch any PHP notices that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Warning</b>"));
	}


	public function acquireTimeMapTestData() {
		return acquireCSVDataFromFile(
			getenv('TESTDATADIR') . '/timemap-testdata.csv', 4);
	}

	public function acquireTimeMap404Urls() {
		return acquireLinesFromFile(
			getenv('TESTDATADIR') . '/timemap-dneurls-testdata.csv');
	}
}
