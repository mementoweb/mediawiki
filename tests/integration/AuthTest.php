<?php
require_once("HTTPFetch.php");

require_once('PHPUnit/Extensions/TestDecorator.php');

$HOST = $_ENV["TESTHOST"];

class AuthTest extends PHPUnit_Framework_TestCase {

	protected function setUp() {
		global $sessionCookieString;

		$sessionCookieString = authenticateWithMediawiki();
	}

	protected function tearDown() {

		logOutOfMediawiki();
	}

	public function testAuth() {

		global $sessionCookieString;

		global $HOST;

		$uagent = "Memento-Mediawiki-Plugin/Test";

		$response = `curl -s -e '$uagent' -b '$sessionCookieString' -k -i --url 'http://localhost/~smj/mediawiki-1.21.1/index.php?title=Main_Page'`;


	}

}

?>
