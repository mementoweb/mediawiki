<?php

require_once('MockMediawiki.php');
require_once('MementoConfig.php');
require_once('MementoResource.php');
require_once('TimeMapResource.php');
require_once('TimeMapFullResource.php');


class TimeMapPageTest extends PHPUnit_Framework_TestCase {

	public function testgenerateTimeMapText() {

		$out = new MockOutputPage();
		$conf = new MementoConfig();
		$dbr = new MockDatabaseBase();
		$urlparam = "http://www.example.com/wiki/index.php/MyPage";
		$title = new MockTitleObject();

		$tm = new TimeMapFullResource($out, $conf, $dbr, $urlparam, $title, null );

		$data = array(
			array(
				'rev_id' => 1,
				'rev_timestamp' => 'Good Date 1'
			),
			array(
				'rev_id' => 2,
				'rev_timestamp' => 'Good Date 2'
			),
			array(
				'rev_id' => 3,
				'rev_timestamp' => 'Good Date 3'
			)
		);

		$baseURL = "http://www.example.com/wiki/index.php";
		$title = "MyPage";

		$output = $tm->generateTimeMapText(
			$data, $urlparam, $baseURL, $title, $urlparam
			);

		$expectedOutput = <<<EOT
<http://www.example.com/wiki/index.php/MyPage>;rel="original latest-version",
<http://www.example.com/wiki/index.php/Special:TimeMap/http://www.example.com/wiki/index.php/MyPage>;rel="self";from="Good Date 3";until="Good Date 1",
<http://www.example.com/wiki/index.php/Special:TimeGate/http://www.example.com/wiki/index.php/MyPage>;rel="timegate",
<http://www.example.com/wiki/index.php?title=MyPage&oldid=3>;rel="memento";datetime="Good Date 3",
<http://www.example.com/wiki/index.php?title=MyPage&oldid=2>;rel="memento";datetime="Good Date 2",
<http://www.example.com/wiki/index.php?title=MyPage&oldid=1>;rel="memento";datetime="Good Date 1"
EOT;

		$this->assertEquals(count($expectedOutputA), count($outputA));

		$this->assertEquals($expectedOutput, $output);

	}

	public function testgenerateTimeMapTextPivotIncreasing() {

		$out = new MockOutputPage();
		$conf = new MementoConfig();
		$dbr = new MockDatabaseBase();
		$urlparam = "20130609170256/1/http://www.example.com/wiki/index.php/MyPage";
		$title = new MockTitleObject();

		$tm = new TimeMapFullResource($out, $conf, $dbr, $urlparam, $title, null );

		$data = array(
			array(
				'rev_id' => 1,
				'rev_timestamp' => 'Good Date 1'
			),
			array(
				'rev_id' => 2,
				'rev_timestamp' => 'Good Date 2'
			),
			array(
				'rev_id' => 3,
				'rev_timestamp' => 'Good Date 3'
			)
		);

		$baseURL = "http://www.example.com/wiki/index.php";
		$title = "MyPage";

		$pageURL = $tm->extractPageURL($urlparam);

		$output = $tm->generateTimeMapText(
			$data, $urlparam, $baseURL, $title, $pageURL
			);

		$expectedOutput = <<<EOT
<http://www.example.com/wiki/index.php/MyPage>;rel="original latest-version",
<http://www.example.com/wiki/index.php/Special:TimeMap/20130609170256/1/http://www.example.com/wiki/index.php/MyPage>;rel="self";from="Good Date 3";until="Good Date 1",
<http://www.example.com/wiki/index.php/Special:TimeGate/http://www.example.com/wiki/index.php/MyPage>;rel="timegate",
<http://www.example.com/wiki/index.php?title=MyPage&oldid=3>;rel="memento";datetime="Good Date 3",
<http://www.example.com/wiki/index.php?title=MyPage&oldid=2>;rel="memento";datetime="Good Date 2",
<http://www.example.com/wiki/index.php?title=MyPage&oldid=1>;rel="memento";datetime="Good Date 1"
EOT;

		$this->assertEquals(count($expectedOutputA), count($outputA));

		$this->assertEquals($expectedOutput, $output);

	}

	public function testExtractTimestampPivot() {
		
		$out = new MockOutputPage();
		$conf = new MementoConfig();
		$dbr = new MockDatabaseBase();
		$urlparam = "20130609170256/1/http://localhost/~smj/mediawiki-1.21.1/index.php/Awesome_Page";
		$title = new MockTitleObject();

		$tm = new TimeMapFullResource($out, $conf, $dbr, $urlparam, $title, null );

		$expected = "20130609170256";

		$actual = $tm->extractTimestampPivot( $urlparam );

		$this->assertEquals( $expected, $actual );
	}

	public function testExtractBadTimestampPivot() {
		
		$out = new MockOutputPage();
		$conf = new MementoConfig();
		$dbr = new MockDatabaseBase();
		$urlparam = "http://localhost/~smj/mediawiki-1.21.1/index.php/Awesome_Page/01346";
		$title = new MockTitleObject();

		$tm = new TimeMapFullResource($out, $conf, $dbr, $urlparam, $title, null );

		$expected = null;

		$actual = $tm->extractTimestampPivot( $urlparam );

		$this->assertEquals( $expected, $actual );
	}

	public function testFormatTimestampForDatabase() {
		
		$out = new MockOutputPage();
		$conf = new MementoConfig();
		$dbr = new MockDatabaseBase();
		$urlparam = "20130609170256/1/http://localhost/~smj/mediawiki-1.21.1/index.php/Awesome_Page";
		$title = new MockTitleObject();

		$tm = new TimeMapFullResource($out, $conf, $dbr, $urlparam, $title, null );

		$timestamp = "20130609170256";
		$expected = "STANDARDIZED:$timestamp";

		$actual = $tm->formatTimestampForDatabase( $timestamp );

		$this->assertEquals( $expected, $actual );

	}

	public function testFormatTimestampForDatabaseBadInput() {
		
		$out = new MockOutputPage();
		$conf = new MementoConfig();
		$dbr = new MockDatabaseBase();
		$urlparam = "20130609170256/1/http://localhost/~smj/mediawiki-1.21.1/index.php/Awesome_Page";
		$title = new MockTitleObject();

		$tm = new TimeMapFullResource($out, $conf, $dbr, $urlparam, $title, null );

		$timestamp = "BAD INPUT: 13435626";
		$expected = null;

		$actual = $tm->formatTimestampForDatabase( $timestamp );

		$this->assertEquals( $expected, $actual );

	}

	public function testExtractPageURL() {

		$out = new MockOutputPage();
		$conf = new MementoConfig();
		$dbr = new MockDatabaseBase();
		$urlparam = "20130609170256/1/http://localhost/~smj/mediawiki-1.21.1/index.php/Awesome_Page";
		$title = new MockTitleObject();

		$tm = new TimeMapFullResource($out, $conf, $dbr, $urlparam, $title, null );

		$expected = "http://localhost/~smj/mediawiki-1.21.1/index.php/Awesome_Page";

		$actual = $tm->extractPageURL($urlparam);

		$this->assertEquals( $expected, $actual );

	}

	public function testExtractPageURLBadInput() {

		$out = new MockOutputPage();
		$conf = new MementoConfig();
		$dbr = new MockDatabaseBase();
		$urlparam = "20130609170256/1/http://localhost/~smj/mediawiki-1.21.1/index.php/Awesome_Page";
		$title = new MockTitleObject();

		$tm = new TimeMapFullResource($out, $conf, $dbr, $urlparam, $title, null );

		$expected = null;

		$actual = $tm->extractPageURL("This is not good input");

		$this->assertEquals( $expected, $actual );

	}

}

?>
