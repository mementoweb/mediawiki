<?php
require_once("HTTPFetch.php");
require_once("MementoParse.php");
require_once("TestSupport.php");
require_once('PHPUnit/Extensions/TestDecorator.php');

error_reporting(E_ALL | E_NOTICE | E_STRICT);

$TGDEBUG = false;

class TimeGateTest extends PHPUnit_Framework_TestCase {

	public static function setUpBeforeClass() {
		global $sessionCookieString;

		$sessionCookieString = authenticateWithMediawiki();
	}

	public static function tearDownAfterClass() {

		logOutOfMediawiki();
	}

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

		global $sessionCookieString;

		global $TGDEBUG;

		$uagent = "Memento-Mediawiki-Plugin/Test";

        # UA --- GET $URIG; Accept-DateTime: T ------> URI-G
        # UA <--- 302; Location: URI-M; Vary; Link: URI-R, URI-T --- URI-G
		$response = `curl -s -e '$uagent' -b '$sessionCookieString' -k -i -H 'Accept-Datetime: $ACCEPTDATETIME' --url '$URIG'`;

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
        $this->assertEquals("302", $statusline["code"]);
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
        //$this->assertContains('negotiate', $varyItems);
        $this->assertContains('Accept-Datetime', $varyItems);

        $this->assertEquals($headers['Location'], $URIM);
	}

	/**
	 * @group traditionalErrorPages
     * 
	 * @dataProvider acquireTimeGate200Urls
	 */
	public function test400TimeGate($URIG) {

		global $sessionCookieString;

		global $TGDEBUG;

		$uagent = "Memento-Mediawiki-Plugin/Test";

		$response = `curl -s -e '$uagent' -b '$sessionCookieString' -k -i -H 'Accept-Datetime: bad-input' --url '$URIG'`;

		if ($TGDEBUG) {
			echo "\n";
			echo $response . "\n";
			echo "\n";
		}

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

		/*
        $this->assertEquals($relations['first memento']['url'],
            $FIRSTMEMENTO); 
        $this->assertEquals($relations['last memento']['url'],
            $LASTMEMENTO);
        $this->assertEquals($relations['next successor-version memento']['url'],            $NEXTSUCCESSOR);
		*/

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

		global $TGDEBUG;

		$uagent = "Memento-Mediawiki-Plugin/Test";

		$response = `curl -s -e '$uagent' -b '$sessionCookieString' -k -i -H 'Accept-Datetime: bad-input' --url '$URIG'`;

		if ($TGDEBUG) {
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

		global $TGDEBUG;

		$uagent = "Memento-Mediawiki-Plugin/Test";

		$response = `curl -s -e '$uagent' -b '$sessionCookieString' -k -i --url '$URIG'`;

		if ($TGDEBUG) {
			echo "\n";
			echo $response . "\n";
			echo "\n";
		}

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

		global $TGDEBUG;

		$uagent = "Memento-Mediawiki-Plugin/Test";

		$response = `curl -s -e '$uagent' -b '$sessionCookieString' -k -i --url '$URIG'`;

		if ($TGDEBUG) {
			echo "\n";
			echo $response . "\n";
			echo "\n";
		}

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

		$response = `curl -s -X POST -e '$uagent' -b '$sessionCookieString' -k -i --url '$URIG'`;

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
		$response = `curl -s -X POST -e '$uagent' -b '$sessionCookieString' -k -i --url '$URIG'`;

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
