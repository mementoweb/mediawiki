<?php

require_once("MockMediawiki.php");

require_once("memento.php");

error_reporting(E_ALL | E_NOTICE | E_STRICT);


class MementoTest extends PHPUnit_Framework_TestCase {

	public function testConstructLinkHeaderMinimal() {
		$first = array();
		$last = array();

		$first['uri'] = "first url";
		$first['dt'] = "first dt";

		$last['uri'] = "last url";
		$last['dt'] = "last dt";

		$actual = Memento::constructLinkHeader($first, $last);

		$expected = '<first url>;rel="first memento";datetime="first dt", <last url>;rel="last memento";datetime="last dt", ';

		$this->assertEquals($expected, $actual);

	}

	public function testConstructLinkHeaderTotal() {
		$first = array();
		$last = array();
		$mem = array();
		$next = array();
		$prev = array();

		$first['uri'] = "first url";
		$first['dt'] = "first dt";

		$last['uri'] = "last url";
		$last['dt'] = "last dt";

		$mem['uri'] = "memento url";
		$mem['dt'] = "memento dt";

		$next['uri'] = "next url";
		$next['dt'] = "next dt";

		$prev['uri'] = "prev url";
		$prev['dt'] = "prev dt";

		$actual = Memento::constructLinkHeader($first, $last, $mem, $next, $prev);

		$expected = '<first url>;rel="first memento";datetime="first dt", <last url>;rel="last memento";datetime="last dt", <prev url>;rel="prev predecessor-version memento";datetime="prev dt", <next url>;rel="next successor-version memento";datetime="next dt", <memento url>;rel="memento";datetime="memento dt", ';

		$this->assertEquals($expected, $actual);

	}

	public function testConstructLinkHeaderNoMemento() {
		$first = array();
		$last = array();
		$mem = array();
		$next = array();
		$prev = array();

		$first['uri'] = "first url";
		$first['dt'] = "first dt";

		$last['uri'] = "last url";
		$last['dt'] = "last dt";

		$next['uri'] = "next url";
		$next['dt'] = "next dt";

		$prev['uri'] = "prev url";
		$prev['dt'] = "prev dt";

		$actual = Memento::constructLinkHeader($first, $last, $mem="", $next=$next, $prev=$prev);

		$expected = '<first url>;rel="first memento";datetime="first dt", <last url>;rel="last memento";datetime="last dt", <prev url>;rel="prev predecessor-version memento";datetime="prev dt", <next url>;rel="next successor-version memento";datetime="next dt", '; 

		$this->assertEquals($expected, $actual);

	}

	public function testConstructLinkHeaderNoMementoNoPrev() {
		$first = array();
		$last = array();
		$next = array();

		$first['uri'] = "first url";
		$first['dt'] = "first dt";

		$last['uri'] = "last url";
		$last['dt'] = "last dt";

		$next['uri'] = "next url";
		$next['dt'] = "next dt";

		$actual = Memento::constructLinkHeader($first, $last, $mem="", $next=$next, $prev="");

		$expected = '<first url>;rel="first memento";datetime="first dt", <last url>;rel="last memento";datetime="last dt", <next url>;rel="next successor-version memento";datetime="next dt", ';

		$this->assertEquals($expected, $actual);

	}

	public function testConstructLinkHeaderNoMementoNoNext() {
		$first = array();
		$last = array();
		$prev = array();

		$first['uri'] = "first url";
		$first['dt'] = "first dt";

		$last['uri'] = "last url";
		$last['dt'] = "last dt";

		$prev['uri'] = "prev url";
		$prev['dt'] = "prev dt";

		$actual = Memento::constructLinkHeader($first, $last, $mem="", $next="", $prev=$prev);

		$expected = '<first url>;rel="first memento";datetime="first dt", <last url>;rel="last memento";datetime="last dt", <prev url>;rel="prev predecessor-version memento";datetime="prev dt", ';

		$this->assertEquals($expected, $actual);

	}

	public function testConstructLinkHeaderNoNextNoPrev() {
		$first = array();
		$last = array();
		$mem = array();

		$first['uri'] = "first url";
		$first['dt'] = "first dt";

		$last['uri'] = "last url";
		$last['dt'] = "last dt";

		$mem['uri'] = "memento url";
		$mem['dt'] = "memento dt";


		$actual = Memento::constructLinkHeader($first, $last, $mem);

		$expected = '<first url>;rel="first memento";datetime="first dt", <last url>;rel="last memento";datetime="last dt", <memento url>;rel="memento";datetime="memento dt", ';

		$this->assertEquals($expected, $actual);

	}

	public function testConstructLinkHeaderNoPrev() {
		$first = array();
		$last = array();
		$mem = array();
		$next = array();

		$first['uri'] = "first url";
		$first['dt'] = "first dt";

		$last['uri'] = "last url";
		$last['dt'] = "last dt";

		$mem['uri'] = "memento url";
		$mem['dt'] = "memento dt";

		$next['uri'] = "next url";
		$next['dt'] = "next dt";

		$actual = Memento::constructLinkHeader($first, $last, $mem, $next);

		$expected = '<first url>;rel="first memento";datetime="first dt", <last url>;rel="last memento";datetime="last dt", <next url>;rel="next successor-version memento";datetime="next dt", <memento url>;rel="memento";datetime="memento dt", ';

		$this->assertEquals($expected, $actual);

	}

	public function testGetMementoDbSortOrderFirst() {

		$relType = 'first';

		$expected = "rev_timestamp ASC";
		$actual = Memento::getMementoDbSortOrder($relType);

		$this->assertEquals($expected, $actual);
	}

	public function testGetMementoDbSortOrderLast() {

		$relType = 'last';

		$expected = "rev_timestamp DESC";
		$actual = Memento::getMementoDbSortOrder($relType);

		$this->assertEquals($expected, $actual);
	}

	public function testGetMementoDbSortOrderNext() {

		$relType = 'next';

		$expected = "rev_timestamp ASC";
		$actual = Memento::getMementoDbSortOrder($relType);

		$this->assertEquals($expected, $actual);
	}

	public function testGetMementoDbSortOrderPrev() {

		$relType = 'prev';

		$expected = "rev_timestamp DESC";
		$actual = Memento::getMementoDbSortOrder($relType);

		$this->assertEquals($expected, $actual);
	}

	public function testGetMementoDbSortOrderMemento() {

		$relType = 'memento';

		$expected = "rev_timestamp DESC";
		$actual = Memento::getMementoDbSortOrder($relType);

		$this->assertEquals($expected, $actual);
	}

	public function testGetMementoDbSortOrderBadInput() {

		$relType = 'bad input';

		$expected = "";
		$actual = Memento::getMementoDbSortOrder($relType);

		$this->assertEquals($expected, $actual);
	}

	public function testBuildMementoDbConditionFirstTsSet() {
		
		$relType = 'first';
		$pg_ts = 123456789;
		$pg_id = 42;
		$dbr = new MockDatabaseBase();

		$expected = array(
			"rev_page" => $pg_id,
			"rev_timestamp<=" . $dbr->addQuotes($pg_ts)
		);

		$actual = Memento::buildMementoDbCondition(
			$relType, $pg_ts, $pg_id, $dbr);

		$this->assertSame($expected, $actual);

	}

	public function testBuildMementoDbConditionFirstNotSet() {
		
		$relType = 'first';
		$pg_id = 42;
		$dbr = new MockDatabaseBase();

		$expected = array("rev_page" => $pg_id );

		$actual = Memento::buildMementoDbCondition(
			$relType, null, $pg_id, $dbr);

		$this->assertSame($expected, $actual);

	}

	public function testBuildMementoDbConditionLastTsSet() {
		
		$relType = 'last';
		$pg_ts = 123456789;
		$pg_id = 42;
		$dbr = new MockDatabaseBase();

		$expected = array(
			"rev_page" => $pg_id,
			"rev_timestamp>=" . $dbr->addQuotes($pg_ts)
		);

		$actual = Memento::buildMementoDbCondition(
			$relType, $pg_ts, $pg_id, $dbr);

		$this->assertSame($expected, $actual);

	}

	public function testBuildMementoDbConditionLastNotSet() {
		
		$relType = 'last';
		$pg_id = 42;
		$dbr = new MockDatabaseBase();

		$expected = array("rev_page" => $pg_id );

		$actual = Memento::buildMementoDbCondition(
			$relType, null, $pg_id, $dbr);

		$this->assertSame($expected, $actual);

	}

	public function testBuildMementoDbConditionNext() {
		
		$relType = 'next';
		$pg_ts = 123456789;
		$pg_id = 42;
		$dbr = new MockDatabaseBase();

		$expected = array(
			"rev_page" => $pg_id,
			"rev_timestamp>" . $dbr->addQuotes($pg_ts)
		);

		$actual = Memento::buildMementoDbCondition(
			$relType, $pg_ts, $pg_id, $dbr);

		$this->assertSame($expected, $actual);

	}

	public function testBuildMementoDbConditionPrev() {
		
		$relType = 'prev';
		$pg_ts = 123456789;
		$pg_id = 42;
		$dbr = new MockDatabaseBase();

		$expected = array(
			"rev_page" => $pg_id,
			"rev_timestamp<" . $dbr->addQuotes($pg_ts)
		);

		$actual = Memento::buildMementoDbCondition(
			$relType, $pg_ts, $pg_id, $dbr);

		$this->assertSame($expected, $actual);

	}

	public function testBuildMementoDbConditionMemento() {
		
		$relType = 'memento';
		$pg_ts = 123456789;
		$pg_id = 42;
		$dbr = new MockDatabaseBase();

		$expected = array(
			"rev_page" => $pg_id,
			"rev_timestamp<=" . $dbr->addQuotes($pg_ts)
		);

		$actual = Memento::buildMementoDbCondition(
			$relType, $pg_ts, $pg_id, $dbr);

		$this->assertSame($expected, $actual);
	}

	public function testFetchMementoRevisionFromDb() {

		$dbr = new MockDatabaseBase();
		$sqlCond = "My Condition";
		$sqlOrder = "My Order";
		$title = "My Title";
		$waddress = "/something/somewhere.ext";

		$actual = Memento::fetchMementoRevisionFromDb(
			$dbr, $sqlCond, $sqlOrder, $title, $waddress);

		$expected = array(
			'uri' => 
				'PROCESSED:EXPANDED:/something/somewhere.ext [title = My Title][oldid = 42]',
			'dt' => "STANDARDIZED:123456789"
		);

		$this->assertSame($expected, $actual);

	}


}
?>
