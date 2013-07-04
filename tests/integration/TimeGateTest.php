<?php
require_once("HTTPFetch.php");
require_once("MementoParse.php");
require_once("TestSupport.php");
require_once('PHPUnit/Extensions/TestDecorator.php');


error_reporting(E_ALL | E_NOTICE | E_STRICT);

$HOST = $_ENV["TESTHOST"];
$TGDEBUG = false;

class TimeGateTest extends PHPUnit_Framework_TestCase {

	/**
	 * @group all
	 * 
	 * @dataProvider acquireTimeGatesWithAcceptDateTime
	 */
	public function test302TimeGate(
            $ACCEPTDATETIME,
            $URIR,
            $FIRSTMEMENTO,
            $LASTMEMENTO,
            $NEXTSUCCESSOR,
            $URIM,
			$URIG,
			$URIT
		) {

		global $HOST;
		global $TGDEBUG;

        # UA --- GET $URIG; Accept-DateTime: T ------> URI-G
        $request = "GET $URIG HTTP/1.1\r\n";
        $request .= "Host: $HOST\r\n";
        $request .= "Accept-Datetime: $ACCEPTDATETIME\r\n";
        $request .= "Connection: close\r\n\r\n";

        # UA <--- 302; Location: URI-M; Vary; Link: URI-R, URI-T --- URI-G
        $response = HTTPFetch('localhost', 80, $request);

        if ($TGDEBUG) {
            echo "\n";
            echo $response;
            echo "\n";
        }

        $headers = extractHeadersFromResponse($response);
        $statusline = extractStatuslineFromResponse($response);
		$entity = extractEntityFromResponse($response);

		if ($entity) {
			$this->fail("302 response should not contain entity for $URIG");
		}

        # 302, Location, Vary, Link
        $this->assertEquals($statusline["code"], "302");
        $this->assertArrayHasKey('Location', $headers);
        $this->assertArrayHasKey('Vary', $headers);
        $this->assertArrayHasKey('Link', $headers);

        $relations = extractItemsFromLink($headers['Link']);
        $varyItems = extractItemsFromVary($headers['Vary']);

        # Link
        $this->assertArrayHasKey('first memento', $relations);
        $this->assertArrayHasKey('last memento', $relations);
        $this->assertArrayHasKey('next successor-version memento', $relations);
        $this->assertArrayHasKey('original latest-version', $relations);
        $this->assertArrayHasKey('timemap', $relations);

        # Link: URI-R
        $this->assertEquals($URIR, 
            $relations['original latest-version']['url']);

        # Link: URI-T
        $this->assertContains("<$URIT>; rel=\"timemap\"", $headers['Link']);
        $this->assertEquals($URIT, $relations['timemap']['url']);

        # Link: other entries
        $this->assertNotNull($relations['first memento']['datetime']);
        $this->assertNotNull($relations['last memento']['datetime']);
        $this->assertNotNull(
            $relations['next successor-version memento']['datetime']);
        $this->assertEquals($relations['first memento']['url'],
            $FIRSTMEMENTO); 
        $this->assertEquals($relations['last memento']['url'],
            $LASTMEMENTO);
        $this->assertEquals($relations['next successor-version memento']['url'],            $NEXTSUCCESSOR);

        # Vary: appropriate entries
        $this->assertContains('negotiate', $varyItems);
        $this->assertContains('accept-datetime', $varyItems);

        $this->assertEquals($headers['Location'], $URIM);
	}

	/**
	 * @group traditionalErrorPages
     * 
	 * @dataProvider acquireTimeGate200Urls
	 */
	public function test400TimeGate($URIG) {
		global $HOST;
		global $TGDEBUG;

        $request = "GET $URIG HTTP/1.1\r\n";
        $request .= "Host: $HOST\r\n";
		$request .= "Accept-DateTime:  bad-input\r\n";
        $request .= "Connection: close\r\n\r\n";

		$response = HTTPFetch($HOST, 80, $request);

		if ($TGDEBUG) {
			echo "\n";
			echo $response . "\n";
			echo "\n";
		}

		$statusline = extractStatusLineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        $this->assertEquals($statusline["code"], "400");

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
		global $HOST;
		global $TGDEBUG;

        $request = "GET $URIG HTTP/1.1\r\n";
        $request .= "Host: $HOST\r\n";
		$request .= "Accept-DateTime:  bad-input\r\n";
        $request .= "Connection: close\r\n\r\n";

		$response = HTTPFetch($HOST, 80, $request);

		if ($TGDEBUG) {
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
		$expected = acquireFormattedI18NString('en', 'timegate-400-date');
		$this->assertStringMatchesFormat("$expected", $entity);
	}

	/**
	 * @group traditionalErrorPages
     * 
	 * @dataProvider acquireTimeGate404Urls
	 */
	public function test404TimeGate($URIG) {
	
		global $HOST;
		global $TGDEBUG;

        $request = "GET $URIG HTTP/1.1\r\n";
        $request .= "Host: $HOST\r\n";
        $request .= "Connection: close\r\n\r\n";

		$response = HTTPFetch($HOST, 80, $request);

		if ($TGDEBUG) {
			echo "\n";
			echo $response . "\n";
			echo "\n";
		}

		$statusline = extractStatusLineFromResponse($response);
        $headers = extractHeadersFromResponse($response);
		$entity = extractEntityFromResponse($response);

        $this->assertEquals($statusline["code"], "404");
		$this->assertEquals($headers["Vary"], "negotiate,accept-datetime");

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
	
		global $HOST;
		global $TGDEBUG;

        $request = "GET $URIG HTTP/1.1\r\n";
        $request .= "Host: $HOST\r\n";
        $request .= "Connection: close\r\n\r\n";

		$response = HTTPFetch($HOST, 80, $request);

		if ($TGDEBUG) {
			echo "\n";
			echo $response . "\n";
			echo "\n";
		}

		$statusline = extractStatusLineFromResponse($response);
        $headers = extractHeadersFromResponse($response);
		$entity = extractEntityFromResponse($response);

        $this->assertEquals($statusline["code"], "200");
		$this->assertEquals($headers["Vary"], "negotiate,accept-datetime");

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
	 * @group traditionalErrorPages
     * 
	 * @dataProvider acquireTimeGate405Urls
	 */
	public function test405TimeGate($URIG) {
		global $HOST;

        $request = "POST $URIG HTTP/1.1\r\n";
        $request .= "Host: $HOST\r\n";
        $request .= "Connection: close\r\n\r\n";

		$response = HTTPFetch($HOST, 80, $request);

		$statusline = extractStatusLineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        $this->assertEquals($statusline["code"], "405");

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
		global $HOST;

        $request = "POST $URIG HTTP/1.1\r\n";
        $request .= "Host: $HOST\r\n";
        $request .= "Connection: close\r\n\r\n";

		$response = HTTPFetch($HOST, 80, $request);

		$statusline = extractStatusLineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        $this->assertEquals($statusline["code"], "200");

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


	public function acquireTimeGate404Urls() {
		return acquireLinesFromFile(
			'tests/integration/test-data/timegate404-testdata.csv');
	}

	public function acquireTimeGate405Urls() {
		return acquireLinesFromFile(
			'tests/integration/test-data/timegate405-testdata.csv');
	}

	public function acquireTimeGate200Urls() {
		return acquireLinesFromFile(
			'tests/integration/test-data/timegate200-testdata.csv');
	}

	public function acquireTimeGatesWithAcceptDateTime() {
		return acquireCSVDataFromFile(
			'tests/integration/test-data/timegate-302-testdata.csv', 8);
	}

}
