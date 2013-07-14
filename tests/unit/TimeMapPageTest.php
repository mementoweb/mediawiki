<?php

require_once('MockMediawiki.php');
require_once('MementoConfig.php');
require_once('MementoResource.php');
require_once('TimeMapPage.php');


class TimeMapPageTest extends PHPUnit_Framework_TestCase {

	public function testgenerateTimeMapText() {

		$out = new MockOutputPage();
		$conf = new MementoConfig();
		$dbr = new MockDatabaseBase();
		$urlparam = "http://www.example.com/wiki/index.php/MyPage";
		$title = new MockTitleObject();

		$tm = new TimeMapPage($out, $conf, $dbr, $urlparam, $title);

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
			$data, $urlparam, $baseURL, $title
			);

		$expectedOutput = <<<EOT
<http://www.example.com/wiki/index.php/Special:TimeGate/http://www.example.com/wiki/index.php/MyPage>;rel="timegate",
<http://www.example.com/wiki/index.php/Special:TimeMap/http://www.example.com/wiki/index.php/MyPage>;rel="self";from="Good Date 3";until="Good Date 1",
<http://www.example.com/wiki/index.php/MyPage>;rel="original latest-version",
<http://www.example.com/wiki/index.php?title=MyPage&oldid=3>rel="memento";datetime="Good Date 3",
<http://www.example.com/wiki/index.php?title=MyPage&oldid=2>rel="memento";datetime="Good Date 2",
<http://www.example.com/wiki/index.php?title=MyPage&oldid=1>rel="memento";datetime="Good Date 1",

EOT;

		$this->assertEquals(count($expectedOutputA), count($outputA));

		$this->assertEquals($expectedOutput, $output);

	}

	

}

?>
