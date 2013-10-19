<?php
require_once("HTTPFetch.php");
require_once("MementoParse.php");
require_once("TestSupport.php");
require_once('PHPUnit/Extensions/TestDecorator.php');

error_reporting(E_ALL | E_NOTICE | E_STRICT);

class ErrorStateTest extends PHPUnit_Framework_TestCase {

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

	public function Status400TimeGateErrorResponseCommonTests(
		$URIG, $statuscode, $outputfile, $debugfile) {

		global $sessionCookieString;

		$uagent = "Memento-Mediawiki-Plugin/Test";

		$curlCmd = "curl -v -s -A '$uagent' -b '$sessionCookieString' -k -i -H 'Accept-Datetime: bad-input' --url '$URIG'";
		$response = `$curlCmd 2> $debugfile | tee "$outputfile"`;

		$statusline = extractStatusLineFromResponse($response);
		$entity = extractEntityFromResponse($response);
        $headers = extractHeadersFromResponse($response);

        $this->assertEquals($statuscode, $statusline["code"]);

        $this->assertArrayHasKey('Vary', $headers);

		$varyItems = extractItemsFromVary($headers['Vary']);
        $this->assertContains('Accept-Datetime', $varyItems, "Accept-Datetime not present in Vary header");

		# To catch any PHP errors that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Fatal error</b>"));

		# To catch any PHP notices that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Notice</b>"));

		# To catch any PHP notices that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Warning</b>"));

		# To ensure that the error message actually exists in the output
		$expected = acquireFormattedI18NString('en', 'timegate-400-date');
		$this->assertStringMatchesFormat("$expected", $entity);

	}

	public function Status404TimeMapErrorResponseCommonTests(
		$URIT, $statuscode, $outputfile, $debugfile) {

		global $sessionCookieString;

		$uagent = "Memento-Mediawiki-Plugin/Test";


		$curlCmd = "curl -v -s -A '$uagent' -b '$sessionCookieString' -k -i --url '$URIT'";
		$response = `$curlCmd 2> $debugfile | tee "$outputfile"`;

		$statusline = extractStatusLineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        $this->assertEquals($statuscode, $statusline["code"]);

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

	public function Status400TimeMapErrorResponseCommonTests(
		$URIT, $statuscode, $outputfile, $debugfile) {

		global $sessionCookieString;

		$uagent = "Memento-Mediawiki-Plugin/Test";

		$curlCmd = "curl -v -s -A '$uagent' -b '$sessionCookieString' -k -i --url '$URIT'";
		$response = `$curlCmd 2> $debugfile | tee "$outputfile"`;

		$statusline = extractStatusLineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        $this->assertEquals($statuscode, $statusline["code"]);

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
	 * @group traditionalErrorPages
     * 
	 * @dataProvider acquireTimeNegotiationData
	 */
	public function test400TimeGateTraditionalError(
			$IDENTIFIER,
		    $ACCEPTDATETIME,
		    $URIR,
			$ORIGINALLATEST,
		    $FIRSTMEMENTO,
		    $LASTMEMENTO,
			$PREVPREDECESSOR,
		    $NEXTSUCCESSOR,
		    $URIM,
			$URIG,
			$URIT,
			$COMMENT
		) {
		$outputfile = __CLASS__ . '.' . __FUNCTION__ . '.' . self::$instance . '.txt';
		$debugfile = __CLASS__ . '.' . __FUNCTION__ . '-debug-' . self::$instance . '.txt';
		
		$this->Status400TimeGateErrorResponseCommonTests(
			$URIG, "400", $outputfile, $debugfile);
	}

	/**
	 * @group friendlyErrorPages
     * 
	 * @dataProvider acquireTimeNegotiationData
	 */
	public function test400TimeGateFriendlyError(
			$IDENTIFIER,
		    $ACCEPTDATETIME,
		    $URIR,
			$ORIGINALLATEST,
		    $FIRSTMEMENTO,
		    $LASTMEMENTO,
			$PREVPREDECESSOR,
		    $NEXTSUCCESSOR,
		    $URIM,
			$URIG,
			$URIT,
			$COMMENT
		) {
		$outputfile = __CLASS__ . '.' . __FUNCTION__ . '.' . self::$instance . '.txt';
		$debugfile = __CLASS__ . '.' . __FUNCTION__ . '-debug-' . self::$instance . '.txt';
		
		$this->Status400TimeGateErrorResponseCommonTests(
			$URIG, "200", $outputfile, $debugfile);
	}

	/**
	 * @group traditionalErrorPages
	 *
	 * @dataProvider acquireTimeMap404Urls
	 */
	public function test404TimeMapTraditionalError($URIT) {
		$outputfile = __CLASS__ . '.' . __FUNCTION__ . '.' . self::$instance . '.txt';
		$debugfile = __CLASS__ . '.' . __FUNCTION__ . '-debug-' . self::$instance . '.txt';

		$this->Status404TimeMapErrorResponseCommonTests(
			$URIT, "404", $outputfile, $debugfile);
	}

	/**
	 * @group friendlyErrorPages
	 *
	 * @dataProvider acquireTimeMap404Urls
	 */
	public function test404TimeMapFriendlyError($URIT) {
		$outputfile = __CLASS__ . '.' . __FUNCTION__ . '.' . self::$instance . '.txt';
		$debugfile = __CLASS__ . '.' . __FUNCTION__ . '-debug-' . self::$instance . '.txt';

		$this->Status404TimeMapErrorResponseCommonTests(
			$URIT, "200", $outputfile, $debugfile);
	}

	/**
	 * @group traditionalErrorPages
	 *
	 * @dataProvider acquireTimeNegotiationData
	 */
#	public function test400TimeMapTraditionalError(
#			$IDENTIFIER,
#		    $ACCEPTDATETIME,
#		    $URIR,
#			$ORIGINALLATEST,
#		    $FIRSTMEMENTO,
#		    $LASTMEMENTO,
#			$PREVPREDECESSOR,
#		    $NEXTSUCCESSOR,
#		    $URIM,
#			$URIG,
#			$URIT,
#			$COMMENT
#		) {
#		$outputfile = __CLASS__ . '.' . __FUNCTION__ . '.' . self::$instance . '.txt';
#		$debugfile = __CLASS__ . '.' . __FUNCTION__ . '-debug-' . self::$instance . '.txt';
#
#		$this->Status400TimeMapErrorResponseCommonTests(
#			$URIT, "400", $outputfile, $debugfile);
#	}
#
#	/**
#	 * @group friendlyErrorPages
#	 *
#	 * @dataProvider acquireTimeNegotiationData
#	 */
#	public function test400TimeMapFriendlyError(
#			$IDENTIFIER,
#		    $ACCEPTDATETIME,
#		    $URIR,
#			$ORIGINALLATEST,
#		    $FIRSTMEMENTO,
#		    $LASTMEMENTO,
#			$PREVPREDECESSOR,
#		    $NEXTSUCCESSOR,
#		    $URIM,
#			$URIG,
#			$URIT,
#			$COMMENT
#		) {
#		$outputfile = __CLASS__ . '.' . __FUNCTION__ . '.' . self::$instance . '.txt';
#		$debugfile = __CLASS__ . '.' . __FUNCTION__ . '-debug-' . self::$instance . '.txt';
#
#		$this->Status400TimeMapErrorResponseCommonTests(
#			$URIT, "200", $outputfile, $debugfile);
#	}

	public function acquireTimeNegotiationData() {
		return acquireCSVDataFromFile(
			getenv('TESTDATADIR') . '/time-negotiation-testdata.csv', 12);
	}

	public function acquireTimeMap404Urls() {
		return acquireLinesFromFile(
			getenv('TESTDATADIR') . '/timemap-dneurls-testdata.csv');
	}

}
