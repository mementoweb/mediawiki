<?php

define("MEDIAWIKI", true);

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

}
?>
