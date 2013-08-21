<?php
require_once("HTTPFetch.php");
require_once("MementoParse.php");
require_once("TestSupport.php");
require_once('PHPUnit/Extensions/TestDecorator.php');

error_reporting(E_ALL | E_NOTICE | E_STRICT);

class TimeGateTest extends PHPUnit_Framework_TestCase {

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
	 * @group all
	 * 
	 * @dataProvider acquireTimeGatesWithAcceptDateTime
	 */
	public function test302TimeGate(
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

		global $sessionCookieString;

		$uagent = "Memento-Mediawiki-Plugin/Test";
		$outputfile = __CLASS__ . '.' . __FUNCTION__ . '.' . $IDENTIFIER;
		$debugfile = __CLASS__ . '.' . __FUNCTION__ . '.' . $IDENTIFIER . '-debug.txt';

        # UA --- GET $URIG; Accept-DateTime: T ------> URI-G
        # UA <--- 302; Location: URI-M; Vary; Link: URI-R, URI-T --- URI-G
		$curlCmd = "curl -v -s -e '$uagent' -b '$sessionCookieString' -k -i -H 'Accept-Datetime: $ACCEPTDATETIME' --url \"$URIG\"";
		#echo "running: $curlCmd\n";

		$response = `$curlCmd 2> $debugfile | tee "$outputfile"`;

        $headers = extractHeadersFromResponse($response);
        $statusline = extractStatuslineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        # 302, Location, Vary, Link
		if ($entity) {
			$this->fail("302 response should not contain entity for $URIG\n" . $response );
		}
        $this->assertEquals("302", $statusline["code"], "Status is not 302");

        $this->assertArrayHasKey('Location', $headers, "Location field not present in headers");
        $this->assertArrayHasKey('Vary', $headers, "Vary field not present in headers");
        $this->assertArrayHasKey('Link', $headers, "Link field not present in headers");

        $relations = extractItemsFromLink($headers['Link']);
        $varyItems = extractItemsFromVary($headers['Vary']);

        # Link: URI-R
        $this->assertEquals($ORIGINALLATEST,
            $relations['original latest-version']['url'],
			"'original latest-version' relation does not have the correct value\n" .
			extractHeadersStringFromResponse($response) );

        # Link: URI-T
        $this->assertArrayHasKey('timemap', $relations);
        $this->assertContains("<$URIT>; rel=\"timemap\"", $headers['Link'], "'timemap' relation not present in Link header field\n" . extractHeadersStringFromResponse($response) );
        $this->assertEquals($URIT, $relations['timemap']['url'], "'timemap' relation URL not correct");

        # Link: Other Relations
		if ( ($NEXTSUCCESSOR == 'N/A') and ($PREVPREDECESSOR == 'N/A') ) {
			$this->assertArrayHasKey('first last memento', $relations);
			$this->assertNotNull($relations['first last memento']['datetime']);
			$this->assertEquals($URIM, $relations['first last memento']['url']);
		} else {
        	$this->assertArrayHasKey('first memento', $relations, "'first memento' relation not present in Link field:\n" . extractHeadersStringFromResponse($response) );
        	$this->assertNotNull($relations['first memento']['datetime'], "'first memento' relation does not contain a datetime field\n" . extractHeadersStringFromResponse($response) );

        	$this->assertArrayHasKey('last memento', $relations, "'last memento' relation not present in Link field");
        	$this->assertNotNull($relations['last memento']['datetime'], "'last memento' relation does not contain a datetime field\n" . extractHeadersStringFromResponse($response) );
        	$this->assertEquals($FIRSTMEMENTO,
				$relations['first memento']['url'],
            	"first memento url is not correct\n" . extractHeadersStringFromResponse($response) );

        	$this->assertEquals($LASTMEMENTO,
				$relations['last memento']['url'],
				"last memento url is not correct\n" . extractHeadersStringFromResponse($response) );

			if ($NEXTSUCCESSOR == 'N/A') {
       			$this->assertArrayNotHasKey('next successor-version memento',
					$relations,
					"'next successor-version memento' should not be present in Link field");
			} else {
				$this->assertArrayHasKey('next successor-version memento',
					$relations,
					"'next successor-version memento' not present in Link field");
        		$this->assertNotNull(
        	    	$relations['next successor-version memento']['datetime'],
					"'next successor-version memento' does not contain a datetime field\n" . extractHeadersStringFromResponse($response) );
        		$this->assertEquals($NEXTSUCCESSOR,
					$relations['next successor-version memento']['url'],
					"next successor-version memento url is not correct\n" . extractHeadersStringFromResponse($response) );
			}
		}

        $this->assertArrayHasKey('original latest-version', $relations, "'original latest-version' relation not present in Link field");
        $this->assertArrayHasKey('timemap', $relations, "'timemap' relation not present in Link field");

        # Vary: appropriate entries
        //$this->assertContains('negotiate', $varyItems);
        $this->assertContains('Accept-Datetime', $varyItems, "Accept-Datetime not present in Vary header");

        $this->assertEquals($URIM,
			$headers['Location'], 
			"Location field contains incorrect URL value");
	}

	/**
	 * @group traditionalErrorPages
     * 
	 * @dataProvider acquireTimeGate200Urls
	 */
	public function test400TimeGate($URIG) {

		global $sessionCookieString;

		$uagent = "Memento-Mediawiki-Plugin/Test";

		$outputfile = __CLASS__ . '.' . __FUNCTION__ . '.' . self::$instance . '.txt';
		$debugfile = __CLASS__ . '.' . __FUNCTION__ . '-debug-' . self::$instance . '.txt';

		$curlCmd = "curl -v -s -e '$uagent' -b '$sessionCookieString' -k -i -H 'Accept-Datetime: bad-input' --url '$URIG'";
		$response = `$curlCmd 2> $debugfile | tee "$outputfile"`;

		$statusline = extractStatusLineFromResponse($response);
		$entity = extractEntityFromResponse($response);
        $headers = extractHeadersFromResponse($response);

        $this->assertEquals("400", $statusline["code"]);

        $this->assertArrayHasKey('Vary', $headers);
        $this->assertArrayHasKey('Link', $headers);

        $relations = extractItemsFromLink($headers['Link']);
        $varyItems = extractItemsFromVary($headers['Vary']);

        # Link
        $this->assertArrayHasKey('first memento', $relations);
        $this->assertArrayHasKey('last memento', $relations);
        $this->assertArrayHasKey('original latest-version', $relations);
        $this->assertArrayHasKey('timemap', $relations);

		/*
        # Link: URI-T
        $this->assertContains("<$URIT>; rel=\"timemap\"", $headers['Link']);
        $this->assertEquals($URIT, $relations['timemap']['url']);
		*/

        # Link: other entries
        $this->assertNotNull($relations['first memento']['datetime']);
        $this->assertNotNull($relations['last memento']['datetime']);

		# To catch any PHP errors that the test didn't notice
		$this->assertNotContains("<b>Fatal error</b>", $entity);

		# To catch any PHP notices that the test didn't notice
		$this->assertNotContains("<b>Notice</b>", $entity);

		# To catch any PHP notices that the test didn't notice
		$this->assertNotContains("<b>Warning</b>", $entity);

		# To ensure that the error message actually exists in the output
		$expected = acquireFormattedI18NString('en', 'timegate-400-date');
		$this->assertStringMatchesFormat("$expected", $entity);
	}

	/**
	 * @group friendlyErrorPages
     * 
	 * @dataProvider acquireTimeGate200Urls
	 */
	public function testFriendly400TimeGate($URIG) {
		global $sessionCookieString;

		$uagent = "Memento-Mediawiki-Plugin/Test";

		$outputfile = __CLASS__ . '.' . __FUNCTION__ . '.' . self::$instance . '.txt';
		$debugfile = __CLASS__ . '.' . __FUNCTION__ . '-debug-' . self::$instance . '.txt';

		$curlCmd = "curl -v -s -e '$uagent' -b '$sessionCookieString' -k -i -H 'Accept-Datetime: bad-input' --url '$URIG'";
		$response = `$curlCmd 2> $debugfile | tee "$outputfile"`;

		$statusline = extractStatusLineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        $this->assertEquals("200", $statusline["code"], "expected 200 status code");

		# To catch any PHP errors that the test didn't notice
		$this->assertNotContains("<b>Fatal error</b>", $entity);

		# To catch any PHP notices that the test didn't notice
		$this->assertNotContains("<b>Notice</b>", $entity);

		# To catch any PHP notices that the test didn't notice
		$this->assertNotContains("<b>Warning</b>", $entity);

		# To ensure that the error message actually exists in the output
		$expected = acquireFormattedI18NString('en', 'timegate-400-date');

		$this->assertStringMatchesFormat("%a" . $expected . "%a", $entity);
	}

	/**
	 * @group traditionalErrorPages
     * 
	 * @dataProvider acquireTimeGate404Urls
	 */
	public function test404TimeGate($URIG) {
	
		global $sessionCookieString;

		$uagent = "Memento-Mediawiki-Plugin/Test";

		$outputfile = __CLASS__ . '.' . __FUNCTION__ . '.' . self::$instance . '.txt';
		$debugfile = __CLASS__ . '.' . __FUNCTION__ . '-debug-' . self::$instance . '.txt';

		$curlCmd = "curl -v -s -e '$uagent' -b '$sessionCookieString' -k -i --url '$URIG'";
		$response = `$curlCmd 2> $debugfile | tee "$outputfile"`;

		$statusline = extractStatusLineFromResponse($response);
        $headers = extractHeadersFromResponse($response);
		$entity = extractEntityFromResponse($response);

        $this->assertEquals("404", $statusline["code"]);

        $headers = extractHeadersFromResponse($response);
        $varyItems = extractItemsFromVary($headers['Vary']);
        $this->assertContains('Accept-Datetime', $varyItems);

		# To catch any PHP errors that the test didn't notice
		$this->assertNotContains("<b>Fatal error</b>", $entity);

		# To catch any PHP notices that the test didn't notice
		$this->assertNotContains("<b>Notice</b>", $entity);

		# To catch any PHP notices that the test didn't notice
		$this->assertNotContains("<b>Warning</b>", $entity);

		# To ensure that the error message actually exists in the output
		$expected = acquireFormattedI18NString('en', 'timegate-404-title');
		$this->assertStringMatchesFormat("$expected", $entity);
	}

	/**
	 * @group friendlyErrorPages
     * 
	 * @dataProvider acquireTimeGate404Urls
	 */
	public function testFriendly404TimeGate($URIG) {

		global $sessionCookieString;

		$uagent = "Memento-Mediawiki-Plugin/Test";

		$outputfile = __CLASS__ . '.' . __FUNCTION__ . '.' . self::$instance . '.txt';
		$debugfile = __CLASS__ . '.' . __FUNCTION__ . '-debug-' . self::$instance . '.txt';

		$curlCmd = "curl -v -s -e '$uagent' -b '$sessionCookieString' -k -i --url '$URIG'";
		$response = `$curlCmd 2> $debugfile | tee "$outputfile"`;

		$statusline = extractStatusLineFromResponse($response);
        $headers = extractHeadersFromResponse($response);
		$entity = extractEntityFromResponse($response);

        $this->assertEquals("200", $statusline["code"]);

        $headers = extractHeadersFromResponse($response);
        $varyItems = extractItemsFromVary($headers['Vary']);
        $this->assertContains('Accept-Datetime', $varyItems);

		# To catch any PHP errors that the test didn't notice
		$this->assertNotContains("<b>Fatal error</b>", $entity);

		# To catch any PHP notices that the test didn't notice
		$this->assertNotContains("<b>Notice</b>", $entity);

		# To catch any PHP notices that the test didn't notice
		$this->assertNotContains("<b>Warning</b>", $entity);

		# To ensure that the error message actually exists in the output
		$expected = acquireFormattedI18NString('en', 'timegate-404-title');
		$this->assertStringMatchesFormat("%a" . $expected . "%a", $entity);
	}

	/**
	 * @group traditionalErrorPages
     * 
	 * @dataProvider acquireTimeGate405Urls
	 */
	public function test405TimeGate($URIG) {

		global $sessionCookieString;

		$uagent = "Memento-Mediawiki-Plugin/Test";

		$outputfile = __CLASS__ . '.' . __FUNCTION__ . '.' . self::$instance . '.txt';
		$debugfile = __CLASS__ . '.' . __FUNCTION__ . '-debug-' . self::$instance . '.txt';

		$curlCmd = "curl -v -s -X POST -e '$uagent' -b '$sessionCookieString' -k -i --url '$URIG'";
		$response = `$curlCmd 2> $debugfile | tee "$outputfile"`;

		$statusline = extractStatusLineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        $this->assertEquals("405", $statusline["code"]);

        $headers = extractHeadersFromResponse($response);
        $varyItems = extractItemsFromVary($headers['Vary']);
        $this->assertContains('Accept-Datetime', $varyItems);

		# To catch any PHP errors that the test didn't notice
		if ($entity) {
			$this->assertNotContains("<b>Fatal error</b>", $entity);

			# To catch any PHP notices that the test didn't notice
			$this->assertNotContains("<b>Notice</b>", $entity);

			# To catch any PHP notices that the test didn't notice
			$this->assertNotContains("<b>Warning</b>", $entity);

			# To ensure that the error message actually exists in the output
			$expected = acquireFormattedI18NString('en', 'timegate-405-badmethod');
			$this->assertStringMatchesFormat("$expected", $entity);
		}
	}

	/**
	 * @group friendlyErrorPages
     * 
	 * @dataProvider acquireTimeGate405Urls
	 */
	public function testFriendly405TimeGate($URIG) {

		global $sessionCookieString;

		$uagent = "Memento-Mediawiki-Plugin/Test";

		$outputfile = __CLASS__ . '.' . __FUNCTION__ . '.' . self::$instance . '.txt';
		$debugfile = __CLASS__ . '.' . __FUNCTION__ . '-debug-' . self::$instance . '.txt';

		$curlCmd = "curl -v -s -X POST -e '$uagent' -b '$sessionCookieString' -k -i --url '$URIG'";
		$response = `$curlCmd 2> $debugfile | tee "$outputfile"`;

		$statusline = extractStatusLineFromResponse($response);
		$entity = extractEntityFromResponse($response);
        $headers = extractHeadersFromResponse($response);

        $this->assertEquals("200", $statusline["code"]);

        $headers = extractHeadersFromResponse($response);
        $varyItems = extractItemsFromVary($headers['Vary']);
        $this->assertContains('Accept-Datetime', $varyItems);

		# To catch any PHP errors that the test didn't notice
		if ($entity) {
			$this->assertNotContains("<b>Fatal error</b>", $entity);

			# To catch any PHP notices that the test didn't notice
			$this->assertNotContains("<b>Notice</b>", $entity);

			# To catch any PHP notices that the test didn't notice
			$this->assertNotContains("<b>Warning</b>", $entity);

			# To ensure that the error message actually exists in the output
			$expected = acquireFormattedI18NString('en', 'timegate-405-badmethod');
			$this->assertStringMatchesFormat("%a" . $expected . "%a", $entity);
		}
	}


	public function acquireTimeGate404Urls() {
		return acquireLinesFromFile(
			getenv('TESTDATADIR') . '/timegate-dneurls-testdata.csv');
	}

	public function acquireTimeGate405Urls() {
		return acquireLinesFromFile(
			getenv('TESTDATADIR') . '/timegate-goodurls-testdata.csv');
	}

	public function acquireTimeGate200Urls() {
		return acquireLinesFromFile(
			getenv('TESTDATADIR') . '/timegate-goodurls-testdata.csv');
	}

	public function acquireTimeGatesWithAcceptDateTime() {
		return acquireCSVDataFromFile(
			getenv('TESTDATADIR') . '/full-302-negotiation-testdata.csv', 12);
	}

}
