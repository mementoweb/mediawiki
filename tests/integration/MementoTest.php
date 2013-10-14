<?php
require_once("HTTPFetch.php");
require_once("MementoParse.php");
require_once("TestSupport.php");
require_once('PHPUnit/Extensions/TestDecorator.php');

error_reporting(E_ALL | E_NOTICE | E_STRICT);

class MementoTest extends PHPUnit_Framework_TestCase {

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
	 * @group 302-style
	 *
     * @dataProvider acquire302IntegrationData
     */
    public function test302StyleTimeNegotiationWholeProcess(
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
		$outputfile = __CLASS__ . '.' . __FUNCTION__ . '.' . $IDENTIFIER . '.txt';
		$debugfile = __CLASS__ . '.' . __FUNCTION__ . '.' . $IDENTIFIER . '-debug.txt';

        # UA --- HEAD $URIR; Accept-Datetime: T ----> URI-R
        # UA <--- 200; Link: URI-G ---- URI-R
		$curlCmd = "curl -v -s -A '$uagent' -b '$sessionCookieString' -k -i -H 'Accept-Datetime: $ACCEPTDATETIME' -H \"X-TestComment: $COMMENT\" --url \"$URIR\"";
		#echo '[' . $curlCmd . "]\n";
		$response = `$curlCmd 2> $debugfile | tee -a "$outputfile"`;
		file_put_contents( $outputfile, "\n#########################################\n", FILE_APPEND );
		file_put_contents( $debugfile, "\n#########################################\n", FILE_APPEND );

        $headers = extractHeadersFromResponse($response);
        $statusline = extractStatuslineFromResponse($response);

        $this->assertEquals("200", $statusline["code"]);
        $this->assertArrayHasKey('Link', $headers);

        $relations = extractItemsFromLink($headers['Link']);
        $this->assertContains("<$URIG>; rel=\"timegate\"", $headers['Link']);
        $this->assertArrayHasKey('timegate', $relations);
        $this->assertEquals("$URIG", $relations['timegate']['url']);
        
        # Link: URI-G
        $this->assertContains("<$URIG>; rel=\"timegate\"", $headers['Link']);
        $this->assertEquals("$URIG", $relations['timegate']['url']);

		$this->assertContains("<$URIT>; rel=\"timemap\"", $headers['Link']);
		$this->assertEquals("$URIT", $relations['timemap']['url']);

        # UA --- GET $URIG; Accept-DateTime: T ------> URI-G
        # UA <--- 302; Location: URI-M; Vary; Link: URI-R, URI-T --- URI-G
		$curlCmd = "curl -v -s -A '$uagent' -b '$sessionCookieString' -k -i -H 'Accept-Datetime: $ACCEPTDATETIME' -H \"X-TestComment: $COMMENT\" --url \"$URIG\"";
		$response = `$curlCmd 2>> $debugfile | tee -a "$outputfile"`;

		file_put_contents( $outputfile, "\n#########################################\n", FILE_APPEND );
		file_put_contents( $debugfile, "\n#########################################\n", FILE_APPEND );

        $headers = extractHeadersFromResponse($response);
        $statusline = extractStatuslineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        # 302, Location, Vary, Link
        $this->assertEquals("302", $statusline["code"]);
        $this->assertArrayHasKey('Location', $headers);
        $this->assertArrayHasKey('Vary', $headers);
        $this->assertArrayHasKey('Link', $headers);

		if ($entity) {
			$this->fail("302 response should not contain entity for URI $URIG");
		}

        $relations = extractItemsFromLink($headers['Link']);
        $varyItems = extractItemsFromVary($headers['Vary']);

        # Link: URI-R
        $this->assertEquals($ORIGINALLATEST, 
            $relations['original latest-version']['url'],
			"'original latest-version' relation does not have the correct value" . extractHeadersStringFromResponse($response) );

        # Link: URI-T
        $this->assertArrayHasKey('timemap', $relations);
        $this->assertContains("<$URIT>; rel=\"timemap\"", $headers['Link']);
        $this->assertEquals("$URIT", $relations['timemap']['url']);

        # Vary: appropriate entries
        //$this->assertContains('negotiate', $varyItems);
        $this->assertContains('Accept-Datetime', $varyItems);

        $this->assertEquals($headers['Location'], $URIM);

        # UA --- GET $URIM; Accept-DateTime: T -----> URI-M
        # UA <--- 200; Memento-Datetime: T; Link: URI-R, URI-T, URI-G --- URI-M
		$curlCmd = "curl -v -s -A '$uagent' -b '$sessionCookieString' -k -i -H 'Accept-Datetime: $ACCEPTDATETIME' -H \"X-TestComment: $COMMENT\" --url \"$URIM\"";
		$response = `$curlCmd 2>> $debugfile | tee -a "$outputfile"`;

		file_put_contents( $outputfile, "\n#########################################\n", FILE_APPEND );
		file_put_contents( $debugfile, "\n#########################################\n", FILE_APPEND );

        $headers = extractHeadersFromResponse($response);
        $statusline = extractStatuslineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        # 200, Memento-Datetime, Link
        $this->assertEquals("200", $statusline["code"]);
        $this->assertArrayHasKey('Memento-Datetime', $headers);
        $this->assertArrayHasKey('Link', $headers);

        $relations = extractItemsFromLink($headers['Link']);

        # Link: URI-R
        $this->assertEquals($ORIGINALLATEST,
            $relations['original latest-version']['url'],
			"'original latest-version' relation does not have the correct value");

        # Link: URI-T
        $this->assertArrayHasKey('timemap', $relations);
        $this->assertContains("<$URIT>; rel=\"timemap\"", $headers['Link']);
        $this->assertEquals("$URIT", $relations['timemap']['url']);

        # Link: URI-G
        $this->assertContains("<$URIG>; rel=\"timegate\"", $headers['Link']);
        $this->assertEquals("$URIG", $relations['timegate']['url']);

		# To catch any PHP errors that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Fatal error</b>") == 0 );

		# To catch any PHP notices that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Notice</b>") == 0 );

		# To catch any PHP notices that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Warning</b>") == 0 );
    }

    /**
	 * @group 302-style-recommended-headers
	 *
     * @dataProvider acquire302IntegrationData
     */
    public function test302StyleTimeNegotiationWholeProcessWithRecommendedHeaders(
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
		$outputfile = __CLASS__ . '.' . __FUNCTION__ . '.' . $IDENTIFIER . '.txt';
		$debugfile = __CLASS__ . '.' . __FUNCTION__ . '.' . $IDENTIFIER . '-debug.txt';

        # UA --- HEAD $URIR; Accept-Datetime: T ----> URI-R
        # UA <--- 200; Link: URI-G ---- URI-R
		$curlCmd = "curl -v -s -A '$uagent' -b '$sessionCookieString' -k -i -H 'Accept-Datetime: $ACCEPTDATETIME' -H \"X-TestComment: $COMMENT\" --url \"$URIR\"";
		#echo '[' . $curlCmd . "]\n";
		$response = `$curlCmd 2> $debugfile | tee -a "$outputfile"`;
		file_put_contents( $outputfile, "\n#########################################\n", FILE_APPEND );
		file_put_contents( $debugfile, "\n#########################################\n", FILE_APPEND );

        $headers = extractHeadersFromResponse($response);
        $statusline = extractStatuslineFromResponse($response);

        $this->assertEquals("200", $statusline["code"]);
        $this->assertArrayHasKey('Link', $headers);

        $relations = extractItemsFromLink($headers['Link']);
        $this->assertContains("<$URIG>; rel=\"timegate\"", $headers['Link']);
        $this->assertArrayHasKey('timegate', $relations);
        $this->assertEquals("$URIG", $relations['timegate']['url']);
        
        # Link: URI-G
        $this->assertContains("<$URIG>; rel=\"timegate\"", $headers['Link']);
        $this->assertEquals("$URIG", $relations['timegate']['url']);

		$this->assertContains("<$URIT>; rel=\"timemap\"", $headers['Link']);
		$this->assertEquals("$URIT", $relations['timemap']['url']);

        # UA --- GET $URIG; Accept-DateTime: T ------> URI-G
        # UA <--- 302; Location: URI-M; Vary; Link: URI-R, URI-T --- URI-G
		$curlCmd = "curl -v -s -A '$uagent' -b '$sessionCookieString' -k -i -H 'Accept-Datetime: $ACCEPTDATETIME' -H \"X-TestComment: $COMMENT\" --url \"$URIG\"";
		$response = `$curlCmd 2>> $debugfile | tee -a "$outputfile"`;

		file_put_contents( $outputfile, "\n#########################################\n", FILE_APPEND );
		file_put_contents( $debugfile, "\n#########################################\n", FILE_APPEND );

        $headers = extractHeadersFromResponse($response);
        $statusline = extractStatuslineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        # 302, Location, Vary, Link
        $this->assertEquals("302", $statusline["code"]);
        $this->assertArrayHasKey('Location', $headers);
        $this->assertArrayHasKey('Vary', $headers);
        $this->assertArrayHasKey('Link', $headers);

		if ($entity) {
			$this->fail("302 response should not contain entity for URI $URIG");
		}

        $relations = extractItemsFromLink($headers['Link']);
        $varyItems = extractItemsFromVary($headers['Vary']);

        # Link: URI-R
        $this->assertEquals($ORIGINALLATEST, 
            $relations['original latest-version']['url'],
			"'original latest-version' relation does not have the correct value" . extractHeadersStringFromResponse($response) );

        # Link: URI-T
        $this->assertArrayHasKey('timemap', $relations);
        $this->assertContains("<$URIT>; rel=\"timemap\"", $headers['Link']);
        $this->assertEquals("$URIT", $relations['timemap']['url']);

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

        # Vary: appropriate entries
        //$this->assertContains('negotiate', $varyItems);
        $this->assertContains('Accept-Datetime', $varyItems);

        $this->assertEquals($headers['Location'], $URIM);

        # UA --- GET $URIM; Accept-DateTime: T -----> URI-M
        # UA <--- 200; Memento-Datetime: T; Link: URI-R, URI-T, URI-G --- URI-M
		$curlCmd = "curl -v -s -A '$uagent' -b '$sessionCookieString' -k -i -H 'Accept-Datetime: $ACCEPTDATETIME' -H \"X-TestComment: $COMMENT\" --url \"$URIM\"";
		$response = `$curlCmd 2>> $debugfile | tee -a "$outputfile"`;

		file_put_contents( $outputfile, "\n#########################################\n", FILE_APPEND );
		file_put_contents( $debugfile, "\n#########################################\n", FILE_APPEND );

        $headers = extractHeadersFromResponse($response);
        $statusline = extractStatuslineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        # 200, Memento-Datetime, Link
        $this->assertEquals("200", $statusline["code"]);
        $this->assertArrayHasKey('Memento-Datetime', $headers);
        $this->assertArrayHasKey('Link', $headers);

        $relations = extractItemsFromLink($headers['Link']);

        # Link: URI-R
        $this->assertEquals($ORIGINALLATEST,
            $relations['original latest-version']['url'],
			"'original latest-version' relation does not have the correct value");

        # Link: URI-T
        $this->assertArrayHasKey('timemap', $relations);
        $this->assertContains("<$URIT>; rel=\"timemap\"", $headers['Link']);
        $this->assertEquals("$URIT", $relations['timemap']['url']);

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

        # Link: URI-G
        $this->assertContains("<$URIG>; rel=\"timegate\"", $headers['Link']);
        $this->assertEquals("$URIG", $relations['timegate']['url']);

		# To catch any PHP errors that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Fatal error</b>") == 0 );

		# To catch any PHP notices that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Notice</b>") == 0 );

		# To catch any PHP notices that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Warning</b>") == 0 );
    }

	/**
	 * @group all
	 *
	 * @dataProvider acquireEditUrls
	 */
	public function testEditPage($URIR) {

		global $sessionCookieString;

		$uagent = "Memento-Mediawiki-Plugin/Test";

		$outputfile = __CLASS__ . '.' . __FUNCTION__ . '.' . self::$instance . '.txt';
		$debugfile = __CLASS__ . '.' . __FUNCTION__ . '-debug-' . self::$instance . '.txt';

		$curlCmd = "curl -v -s -A '$uagent' -b '$sessionCookieString' -k -i --url \"$URIR\"";
		$response = `$curlCmd 2> $debugfile | tee "$outputfile"`;

		$statusline = extractStatusLineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        $this->assertEquals("200", $statusline["code"]);

		# To catch any PHP errors that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Fatal error</b>") == 0 );

		# To catch any PHP notices that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Notice</b>") == 0 );

		# To catch any PHP notices that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Warning</b>") == 0 );
	}

	/**
	 * @group 200-style-recommended-headers
	 *
	 * @dataProvider acquire302IntegrationData
     */
    public function test200StyleTimeNegotiation(
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

        # UA --- HEAD $URIR; Accept-Datetime: T ----> URI-R
        # UA <--- 200; Link: URI-G ---- URI-R
		$curlCmd = "curl -v -s -A '$uagent' -b '$sessionCookieString' -k -i -H 'Accept-Datetime: $ACCEPTDATETIME' -H \"X-TestComment: $COMMENT\" --url \"$URIR\"";
		$response = `$curlCmd 2> $debugfile | tee "$outputfile"`;

        $headers = extractHeadersFromResponse($response);
        $statusline = extractStatuslineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        $this->assertEquals("200", $statusline["code"], "200");

        $this->assertArrayHasKey('Link', $headers);
		$this->assertArrayHasKey('Memento-Datetime', $headers);
        $this->assertArrayHasKey('Vary', $headers);
		$this->assertArrayHasKey('Content-Location', $headers);

		$this->assertEquals($URIM, $headers['Content-Location']);

        $relations = extractItemsFromLink($headers['Link']);

        $this->assertArrayHasKey('original timegate', $relations);

		$this->assertEquals($URIR, $relations['original timegate']['url']);

        $varyItems = extractItemsFromVary($headers['Vary']);

        $this->assertContains('Accept-Datetime', $varyItems);

		# To catch any PHP errors that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Fatal error</b>") == 0 );

		# To catch any PHP notices that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Notice</b>") == 0 );

		# To catch any PHP notices that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Warning</b>") == 0 );
	}

	/**
	 * @group 200-style-recommended-headers
	 *
	 * @dataProvider acquire302IntegrationData
     */
    public function test200StyleTimeNegotiationWithRecommendedHeaders(
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

        # UA --- HEAD $URIR; Accept-Datetime: T ----> URI-R
        # UA <--- 200; Link: URI-G ---- URI-R
		$curlCmd = "curl -v -s -A '$uagent' -b '$sessionCookieString' -k -i -H 'Accept-Datetime: $ACCEPTDATETIME' -H \"X-TestComment: $COMMENT\" --url \"$URIR\"";
		$response = `$curlCmd 2> $debugfile | tee "$outputfile"`;

        $headers = extractHeadersFromResponse($response);
        $statusline = extractStatuslineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        $this->assertEquals("200", $statusline["code"], "200");

        $this->assertArrayHasKey('Link', $headers);
		$this->assertArrayHasKey('Memento-Datetime', $headers);
        $this->assertArrayHasKey('Vary', $headers);
		$this->assertArrayHasKey('Content-Location', $headers);

		$this->assertEquals($URIM, $headers['Content-Location']);

        $relations = extractItemsFromLink($headers['Link']);

        $this->assertArrayHasKey('memento first', $relations);
        $this->assertArrayHasKey('memento last', $relations);
        $this->assertArrayHasKey('original timegate', $relations);

        $this->assertNotNull($relations['memento first']['datetime']);
        $this->assertNotNull($relations['memento last']['datetime']);

        $this->assertEquals($FIRSTMEMENTO, $relations['memento first']['url']);
        $this->assertEquals($LASTMEMENTO, $relations['memento last']['url']);
		$this->assertEquals($URIR, $relations['original timegate']['url']);

        $varyItems = extractItemsFromVary($headers['Vary']);

        $this->assertContains('Accept-Datetime', $varyItems);

		# To catch any PHP errors that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Fatal error</b>") == 0 );

		# To catch any PHP notices that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Notice</b>") == 0 );

		# To catch any PHP notices that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Warning</b>") == 0 );
	}

	/**
	 * @group 200-style
	 *
	 * @dataProvider acquire302IntegrationData
     */
    public function test200StyleTimeNegotiationWithoutAcceptDatetime(
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

        # UA --- HEAD $URIR; Accept-Datetime: T ----> URI-R
        # UA <--- 200; Link: URI-G ---- URI-R
		$curlCmd = "curl -v -s -A '$uagent' -b '$sessionCookieString' -H \"X-TestComment: $COMMENT\" -k -i --url \"$URIR\"";
		$response = `$curlCmd 2> $debugfile | tee "$outputfile"`;

        $headers = extractHeadersFromResponse($response);
        $statusline = extractStatuslineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        $this->assertEquals("200", $statusline["code"]);

        $this->assertArrayHasKey('Link', $headers);
        $this->assertArrayHasKey('Vary', $headers);

        $relations = extractItemsFromLink($headers['Link']);

        $this->assertArrayHasKey('original latest-version timegate', $relations);

		$this->assertEquals($ORIGINALLATEST,
			$relations['original latest-version timegate']['url']);

        $varyItems = extractItemsFromVary($headers['Vary']);

        $this->assertContains('Accept-Datetime', $varyItems);

		# To catch any PHP errors that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Fatal error</b>") == 0 );

		# To catch any PHP notices that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Notice</b>") == 0 );

		# To catch any PHP notices that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Warning</b>") == 0 );
	}

	/**
	 * @group 200-style-recommended-headers
	 *
	 * @dataProvider acquire302IntegrationData
     */
    public function test200StyleTimeNegotiationWithoutAcceptDatetimeWithRecommendedHeaders(
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

        # UA --- HEAD $URIR; Accept-Datetime: T ----> URI-R
        # UA <--- 200; Link: URI-G ---- URI-R
		$curlCmd = "curl -v -s -A '$uagent' -b '$sessionCookieString' -H \"X-TestComment: $COMMENT\" -k -i --url \"$URIR\"";
		$response = `$curlCmd 2> $debugfile | tee "$outputfile"`;

        $headers = extractHeadersFromResponse($response);
        $statusline = extractStatuslineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        $this->assertEquals("200", $statusline["code"]);

        $this->assertArrayHasKey('Link', $headers);
        $this->assertArrayHasKey('Vary', $headers);

        $relations = extractItemsFromLink($headers['Link']);

        $this->assertArrayHasKey('original timegate', $relations);

		$this->assertEquals($URIR, $relations['original timegate']['url']);

        $varyItems = extractItemsFromVary($headers['Vary']);

        $this->assertContains('Accept-Datetime', $varyItems);

		# To catch any PHP errors that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Fatal error</b>") == 0 );

		# To catch any PHP notices that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Notice</b>") == 0 );

		# To catch any PHP notices that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Warning</b>") == 0 );
	}

	/**
	 * @group all
	 *
	 * @dataProvider acquireDiffUrls()
	 */
	public function testDiffPage($URIR) {

		global $sessionCookieString;

		$uagent = "Memento-Mediawiki-Plugin/Test";

		$outputfile = __CLASS__ . '.' . __FUNCTION__ . '.' . self::$instance . '.txt';
		$debugfile = __CLASS__ . '.' . __FUNCTION__ . '-debug-' . self::$instance . '.txt';

        # UA <--- 200; Link: URI-G ---- URI-R
		$curlCmd = "curl -v -s -A '$uagent' -b '$sessionCookieString' -k -i --url \"$URIR\"";
		$response = `$curlCmd 2> $debugfile | tee "$outputfile"`;

        $headers = extractHeadersFromResponse($response);
        $statusline = extractStatuslineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        $this->assertEquals($statusline["code"], "200");

		# To catch any PHP errors that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Fatal error</b>") == 0 );

		# To catch any PHP notices that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Notice</b>") == 0 );

		# To catch any PHP notices that the test didn't notice
		$this->assertFalse(strpos($entity, "<b>Warning</b>") == 0 );
	}


    public function acquire302IntegrationData() {
		return acquireCSVDataFromFile(
			getenv('TESTDATADIR') . '/time-negotiation-testdata.csv', 12);
    }

	public function acquireEditUrls() {
		return acquireLinesFromFile(
			getenv('TESTDATADIR') . '/memento-editpage-testdata.csv');
	}

	public function acquireDiffUrls() {
		return acquireLinesFromFile(
			getenv('TESTDATADIR') . '/memento-diffpage-testdata.csv');
	}

}
