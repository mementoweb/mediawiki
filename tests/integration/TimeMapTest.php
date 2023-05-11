<?php
require_once "HTTPFetch.php";
require_once "MementoParse.php";
require_once "TestSupport.php";

error_reporting( E_ALL | E_NOTICE | E_STRICT );

class TimeMapTest extends PHPUnit\Framework\TestCase {
	
	public static $instance = 0;

	public static function setUpBeforeClass() {
		global $sessionCookieString;

		if ( getenv( 'TESTUSERNAME' ) == 'NOAUTH' ) {
			$sessionCookieString = 'TESTING_MEMENTO';
		} else {
			$sessionCookieString = authenticateWithMediawiki();
		}
	}

	public static function tearDownAfterClass() {
		logOutOfMediawiki();
	}

	protected function setUp() {
		self::$instance++;
	}

	/**
	 * @group timemap
	 *
	 */
	public function testSimpleTimeMap() {

		global $sessionCookieString;

		$uagent = "Memento-Mediawiki-Plugin/Test";

		$expected_timemap_file = getenv( 'TESTDATADIR' ) . '/expected_timemap.link';

		$myfile = fopen($expected_timemap_file, "r") or die("cannot open $expected_timemap_file");
		$expected_timemap_data = fread($myfile, filesize($expected_timemap_file));
		fclose($myfile);

		$urit = "http://localhost:8099/index.php/Special:TimeMap/Kevan_Lannister";

		$curlCmd = "curl -v -s -A '$uagent' -b '$sessionCookieString' -k -i --url \"$urit\"";

		$outputfile = __CLASS__ . '.' . __FUNCTION__ . '.' . self::$instance . '.txt';
		$debugfile = __CLASS__ . '.' . __FUNCTION__ . '-debug-' . self::$instance . '.txt';

		$response = `$curlCmd 2> $debugfile | tee -a "$outputfile"`;

		$headers = extractHeadersFromResponse( $response );
		$statusline = extractStatuslineFromResponse( $response );
		$entity = extractEntityFromResponse( $response );

		$this->assertEquals( $statusline["code"], "200" );

		$this->assertEquals( $entity, $expected_timemap_data );

	}

	/**
	 * @group timemap
	 *
	 */
	public function testSimpleIncreasingTimeMap() {

		global $sessionCookieString;

		$uagent = "Memento-Mediawiki-Plugin/Test";

		$expected_timemap_file = getenv( 'TESTDATADIR' ) . '/expected_increasing_timemap.link';

		$myfile = fopen($expected_timemap_file, "r") or die("cannot open $expected_timemap_file");
		$expected_timemap_data = fread($myfile, filesize($expected_timemap_file));
		fclose($myfile);

		$urit = "http://localhost:8099/index.php/Special:TimeMap/20130522211900/1/Kevan_Lannister";

		$curlCmd = "curl -v -s -A '$uagent' -b '$sessionCookieString' -k -i --url \"$urit\"";

		$outputfile = __CLASS__ . '.' . __FUNCTION__ . '.' . self::$instance . '.txt';
		$debugfile = __CLASS__ . '.' . __FUNCTION__ . '-debug-' . self::$instance . '.txt';

		$response = `$curlCmd 2> $debugfile | tee -a "$outputfile"`;

		$headers = extractHeadersFromResponse( $response );
		$statusline = extractStatuslineFromResponse( $response );
		$entity = extractEntityFromResponse( $response );

		$this->assertEquals( $statusline["code"], "200" );

		$this->assertEquals( $entity, $expected_timemap_data );

	}

	/**
	 * @group timemap
	 *
	 */
	public function testSimpleDecreasingTimeMap() {

		global $sessionCookieString;

		$uagent = "Memento-Mediawiki-Plugin/Test";

		$expected_timemap_file = getenv( 'TESTDATADIR' ) . '/expected_decreasing_timemap.link';

		$myfile = fopen($expected_timemap_file, "r") or die("cannot open $expected_timemap_file");
		$expected_timemap_data = fread($myfile, filesize($expected_timemap_file));
		fclose($myfile);

		$urit = "http://localhost:8099/index.php/Special:TimeMap/20130522211900/-1/Kevan_Lannister";

		$curlCmd = "curl -v -s -A '$uagent' -b '$sessionCookieString' -k -i --url \"$urit\"";

		$outputfile = __CLASS__ . '.' . __FUNCTION__ . '.' . self::$instance . '.txt';
		$debugfile = __CLASS__ . '.' . __FUNCTION__ . '-debug-' . self::$instance . '.txt';

		$response = `$curlCmd 2> $debugfile | tee -a "$outputfile"`;

		$headers = extractHeadersFromResponse( $response );
		$statusline = extractStatuslineFromResponse( $response );
		$entity = extractEntityFromResponse( $response );

		$this->assertEquals( $statusline["code"], "200" );

		$this->assertEquals( $entity, $expected_timemap_data );

	}
}
