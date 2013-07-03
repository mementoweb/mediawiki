<?php
require_once("HTTPFetch.php");
require_once("MementoParse.php");
require_once("TestSupport.php");
require_once('PHPUnit/Extensions/TestDecorator.php');

error_reporting(E_ALL | E_NOTICE | E_STRICT);

$HOST = $_ENV["TESTHOST"];
$DEBUG = false;

class MementoTest extends PHPUnit_Framework_TestCase {

    /**
	 * @group all
	 *
     * @dataProvider acquire302IntegrationData
     */
    public function testVaryAcceptDateTime302WholeProcess(
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
        global $DEBUG;

        # UA --- HEAD $URIR; Accept-Datetime: T ----> URI-R
        $request = "GET $URIR HTTP/1.1\r\n";
        $request .= "Host: $HOST\r\n";
        $request .= "Accept-Datetime: $ACCEPTDATETIME\r\n";
        $request .= "Connection: close\r\n\r\n";

        # UA <--- 200; Link: URI-G ---- URI-R
        $response = HTTPFetch('localhost', 80, $request);

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
        $request = "GET $URIG HTTP/1.1\r\n";
        $request .= "Host: $HOST\r\n";
        $request .= "Accept-Datetime: $ACCEPTDATETIME\r\n";
        $request .= "Connection: close\r\n\r\n";

        # UA <--- 302; Location: URI-M; Vary; Link: URI-R, URI-T --- URI-G
        $response = HTTPFetch('localhost', 80, $request);

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
        $this->assertEquals("$URIT", $relations['timemap']['url']);

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

        # UA --- GET $URIM; Accept-DateTime: T -----> URI-M
        $request = "GET $URIM HTTP/1.1\r\n";
        $request .= "Host: $HOST\r\n";
        $request .= "Accept-Datetime: $ACCEPTDATETIME\r\n";
        $request .= "Connection: close\r\n\r\n";

        # UA <--- 200; Memento-Datetime: T; Link: URI-R, URI-T, URI-G --- URI-M
        $response = HTTPFetch('localhost', 80, $request);

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

        # Link
        $this->assertArrayHasKey('first memento', $relations);
        $this->assertArrayHasKey('last memento', $relations);
        $this->assertArrayHasKey('next successor-version memento', $relations);
        $this->assertArrayHasKey('original latest-version', $relations);
        $this->assertArrayHasKey('timemap', $relations);

        $this->assertEquals($relations['first memento']['url'],
            $FIRSTMEMENTO); 
        $this->assertEquals($relations['last memento']['url'],
            $LASTMEMENTO);
        $this->assertEquals($relations['next successor-version memento']['url'],            $NEXTSUCCESSOR);

        # Link: URI-R
        $this->assertEquals($URIR, 
            $relations['original latest-version']['url']);

        # Link: URI-T
        $this->assertContains("<$URIT>; rel=\"timemap\"", $headers['Link']);
        $this->assertEquals("$URIT", $relations['timemap']['url']);

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

		global $HOST;
		global $DEBUG;

        $request = "GET $URIR HTTP/1.1\r\n";
        $request .= "Host: $HOST\r\n";
        $request .= "Connection: close\r\n\r\n";

		$response = HTTPFetch($HOST, 80, $request);

		$statusline = extractStatusLineFromResponse($response);
		$entity = extractEntityFromResponse($response);

        $this->assertEquals($statusline["code"], "200");

        if ($DEBUG) {
            echo "\n";
            echo $entity;
            echo "\n";
        }

		# To catch any PHP errors that the test didn't notice
		$this->assertNotContains("Fatal error", $entity);
	}

    public function acquire302IntegrationData() {
		return acquireCSVDataFromFile(
			'tests/integration/test-data/timegate-302-testdata.csv', 8);
    }

	public function acquireEditUrls() {
		return acquireLinesFromFile(
			'tests/integration/test-data/memento-editpage-testdata.csv');
	}

}
