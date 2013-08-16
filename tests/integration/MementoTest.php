<?php
require_once("HTTPFetch.php");
require_once("MementoParse.php");
require_once("TestSupport.php");
require_once('PHPUnit/Extensions/TestDecorator.php');

error_reporting(E_ALL | E_NOTICE | E_STRICT);

$DEBUG = false;

class MementoTest extends PHPUnit_Framework_TestCase {

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
     * @dataProvider acquire302IntegrationData
     */
    public function testVaryAcceptDateTime302WholeProcess(
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

        global $DEBUG;

		global $sessionCookieString;

		$uagent = "Memento-Mediawiki-Plugin/Test";

        # UA --- HEAD $URIR; Accept-Datetime: T ----> URI-R
        # UA <--- 200; Link: URI-G ---- URI-R
		$response = `curl -s -e '$uagent' -b '$sessionCookieString' -k -i -H 'Accept-Datetime: $ACCEPTDATETIME' --url '$URIR'`;

        if ($DEBUG) {
            echo "\n";
            echo $response;
            echo "\n";
        }

        $headers = extractHeadersFromResponse($response);
        $statusline = extractStatuslineFromResponse($response);

        $this->assertEquals($statusline["code"], "200");
        $this->assertArrayHasKey('Link', $headers);

        $relations = extractItemsFromLink($headers['Link']);
        $this->assertContains("<$URIG>; rel=\"timegate\"", $headers['Link']);
        $this->assertArrayHasKey('timegate', $relations);
        $this->assertEquals("$URIG", $relations['timegate']['url']);
        
        # Link: URI-G
        $URIG = $relations['timegate']['url'];
        $this->assertContains("<$URIG>; rel=\"timegate\"", $headers['Link']);
        $this->assertEquals("$URIG", $relations['timegate']['url']);

        # UA --- GET $URIG; Accept-DateTime: T ------> URI-G
        # UA <--- 302; Location: URI-M; Vary; Link: URI-R, URI-T --- URI-G
		$response = `curl -s -e '$uagent' -b '$sessionCookieString' -k -i -H 'Accept-Datetime: $ACCEPTDATETIME' --url '$URIG'`;

        if ($DEBUG) {
            echo "\n";
            echo $response;
            echo "\n";
        }

        $headers = extractHeadersFromResponse($response);
        $statusline = extractStatuslineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        # 302, Location, Vary, Link
        $this->assertEquals($statusline["code"], "302");
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
		$response = `curl -s -e '$uagent' -b '$sessionCookieString' -k -i -H 'Accept-Datetime: $ACCEPTDATETIME' --url '$URIM'`;

        if ($DEBUG) {
            echo "\n";
            echo $response;
            echo "\n";
        }

        $headers = extractHeadersFromResponse($response);
        $statusline = extractStatuslineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        # 200, Memento-Datetime, Link
        $this->assertEquals($statusline["code"], "200");
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
		$this->assertNotContains("<b>Fatal error</b>", $entity);

		# To catch any PHP notices that the test didn't notice
		$this->assertNotContains("<b>Notice</b>", $entity);

		# To catch any PHP notices that the test didn't notice
		$this->assertNotContains("<b>Warning</b>", $entity);
    }

	/**
	 * @group all
	 *
	 * @dataProvider acquireEditUrls
	 */
	public function testEditPage($URIR) {

		global $DEBUG;

		global $sessionCookieString;

		$uagent = "Memento-Mediawiki-Plugin/Test";

		$response = `curl -s -e '$uagent' -b '$sessionCookieString' -k -i --url '$URIR'`;

		$statusline = extractStatusLineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        $this->assertEquals($statusline["code"], "200");

        if ($DEBUG) {
            echo "\n";
            echo $entity;
            echo "\n";
        }

		# To catch any PHP errors that the test didn't notice
		$this->assertNotContains("<b>Fatal error</b>", $entity);

		# To catch any PHP notices that the test didn't notice
		$this->assertNotContains("<b>Notice</b>", $entity);

		# To catch any PHP notices that the test didn't notice
		$this->assertNotContains("<b>Warning</b>", $entity);
	}

	/**
	 * @group timeNegotiation
	 *
	 * @dataProvider acquire302IntegrationData
     */
    public function testTimeNegotiation(
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

        global $DEBUG;

		global $sessionCookieString;

		$uagent = "Memento-Mediawiki-Plugin/Test";

        # UA --- HEAD $URIR; Accept-Datetime: T ----> URI-R
        # UA <--- 200; Link: URI-G ---- URI-R
		$response = `curl -s -e '$uagent' -b '$sessionCookieString' -k -i -H 'Accept-Datetime: $ACCEPTDATETIME' --url '$URIR'`;

        if ($DEBUG) {
            echo "\n";
            echo $response;
            echo "\n";
        }

        $headers = extractHeadersFromResponse($response);
        $statusline = extractStatuslineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        $this->assertEquals($statusline["code"], "200");

        $this->assertArrayHasKey('Link', $headers);
		$this->assertArrayHasKey('Memento-Datetime', $headers);
        $this->assertArrayHasKey('Vary', $headers);

        $relations = extractItemsFromLink($headers['Link']);

        $this->assertArrayHasKey('memento first', $relations);
        $this->assertArrayHasKey('memento last', $relations);
        $this->assertArrayHasKey('original timegate', $relations);

        $this->assertNotNull($relations['memento first']['datetime']);
        $this->assertNotNull($relations['memento last']['datetime']);

        $this->assertEquals($relations['memento first']['url'], $FIRSTMEMENTO); 
        $this->assertEquals($relations['memento last']['url'], $LASTMEMENTO);
		$this->assertEquals($relations['original timegate']['url'],
			$URIR);

        $varyItems = extractItemsFromVary($headers['Vary']);

        $this->assertContains('Accept-Datetime', $varyItems);

		# To catch any PHP errors that the test didn't notice
		$this->assertNotContains("<b>Fatal error</b>", $entity);

		# To catch any PHP notices that the test didn't notice
		$this->assertNotContains("<b>Notice</b>", $entity);

		# To catch any PHP notices that the test didn't notice
		$this->assertNotContains("<b>Warning</b>", $entity);
	}

	/**
	 * @group timeNegotiation
	 *
	 * @dataProvider acquire302IntegrationData
     */
    public function testTimeNegotiationWithoutAcceptDatetime(
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

        global $DEBUG;

		global $sessionCookieString;

		$uagent = "Memento-Mediawiki-Plugin/Test";

        # UA --- HEAD $URIR; Accept-Datetime: T ----> URI-R
        # UA <--- 200; Link: URI-G ---- URI-R
		$response = `curl -s -e '$uagent' -b '$sessionCookieString' -k -i --url '$URIR'`;

        if ($DEBUG) {
            echo "\n";
            echo $response;
            echo "\n";
        }

        $headers = extractHeadersFromResponse($response);
        $statusline = extractStatuslineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        $this->assertEquals($statusline["code"], "200");

        $this->assertArrayHasKey('Link', $headers);
        $this->assertArrayHasKey('Vary', $headers);

        $relations = extractItemsFromLink($headers['Link']);

        $this->assertArrayHasKey('original timegate', $relations);

		$this->assertEquals($relations['original timegate']['url'],
			$URIR);

        $varyItems = extractItemsFromVary($headers['Vary']);

        $this->assertContains('Accept-Datetime', $varyItems);

		# To catch any PHP errors that the test didn't notice
		$this->assertNotContains("<b>Fatal error</b>", $entity);

		# To catch any PHP notices that the test didn't notice
		$this->assertNotContains("<b>Notice</b>", $entity);

		# To catch any PHP notices that the test didn't notice
		$this->assertNotContains("<b>Warning</b>", $entity);
	}

	/**
	 * @group all
	 *
	 * @dataProvider acquireDiffUrls()
	 */
	public function testDiffPage($URIR) {

        global $DEBUG;

		global $sessionCookieString;

		$uagent = "Memento-Mediawiki-Plugin/Test";

        # UA <--- 200; Link: URI-G ---- URI-R
		$response = `curl -s -e '$uagent' -b '$sessionCookieString' -k -i --url '$URIR'`;

        if ($DEBUG) {
            echo "\n";
            echo $response;
            echo "\n";
        }

        $headers = extractHeadersFromResponse($response);
        $statusline = extractStatuslineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        $this->assertEquals($statusline["code"], "200");

		# To catch any PHP errors that the test didn't notice
		$this->assertNotContains("<b>Fatal error</b>", $entity);

		# To catch any PHP notices that the test didn't notice
		$this->assertNotContains("<b>Notice</b>", $entity);

		# To catch any PHP notices that the test didn't notice
		$this->assertNotContains("<b>Warning</b>", $entity);
	}


    public function acquire302IntegrationData() {
		return acquireCSVDataFromFile(
			getenv('TESTDATADIR') . '/full-302-negotiation-testdata.csv', 11);
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
