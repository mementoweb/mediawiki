<?php

define("MEDIAWIKI", true);
define("TS_RFC2822", "GOOD CONST");

/**
 * Mocked version of wfTimestamp, so we don't need all of Mediawiki
 * to run unit tests.
 */
function wfTimestamp($standard, $input) {
	return "STANDARDIZED:$input";
}

/**
 * Mocked version of wfExpandUrl, so we don't need all of Mediawiki
 * to run unit tests.
 */
function wfExpandUrl($address) {
	return "EXPANDED:$address";
}

/**
 * Mocked version of wfAppendQuery, so we don't need all of Mediawiki
 * to run unit tests.
 */
function wfAppendQuery($address, $queryArray) {

	$string = "PROCESSED:$address ";

	foreach ($queryArray as $key => $value) {
		$string .= "[$key = $value]";
	}

	return $string;
}

/**
 * Mocked version of ResultWrapper, so we don't need a full database
 * for unit testing.
 */
class MockResultWrapper {

	public $rev_id;
	
	public $rev_timestamp;
}

/**
 * Mocked version of the DatabaseBase class, so we don't need
 * a full database for unit testing.
 */
class MockDatabaseBase {

	public function addQuotes($input) {
		return "'$input'";
	}

	public function select($table, $vars, $conds, $fname, $options) {
		return new MockResultWrapper();
	}

	public function fetchObject($res) {
		$res->rev_id = 42;
		$res->rev_timestamp = 123456789;

		return $res; 
	}

}

?>
