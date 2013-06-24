<?php

# ensure that the script can't be executed outside of Mediawiki
if ( ! defined( 'MEDIAWIKI' ) ) {
	echo "Not a valid entry point";
	exit( 1 );
}

/**
 *
 * A Memento TimeGate. See http://mementoweb.org
 *
*/
class TimeGate extends SpecialPage
{


	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct( "TimeGate" );
	}



	/**
	 * The init function that is called by mediawiki when loading this
	 * SpecialPage.
	 * The parameter passed to this function is the original uri.
	 * This function verifies if the article requested is valid and accessible, and
	 * fetches it's page_id, title object, etc. and passes it on to another function that
	 * fetches the memento of the requested resource.
	 *
	 * @param: $par: String.
	 *	  The title parameter that mediawiki returns
	 *	  (the url part after Special:TimeGate/)
	 */
	function execute( $par ) {

		$request = $this->getRequest();
		$out = $this->getOutput();
		global $wgArticlePath;
		global $wgServer;
		global $wgMementoExcludeNamespaces;

		if (!is_array( $wgMementoExcludeNamespaces )) {
			$wgMementoExcludeNamespaces = array();
		}

		$this->setHeaders();

		$requestURL = $request->getRequestURL();
		$mementoResponse = $request->response();

		if ( !$par ) {
			$out->addHTML( wfMessage( 'timegate-welcome-message' )->parse() );
			return;
		}

		if ( $_SERVER['REQUEST_METHOD'] != 'GET' && $_SERVER['REQUEST_METHOD'] != 'HEAD' ) {
			$header = array(
					"Allow" => "GET, HEAD",
					"Vary" => "negotiate, accept-datetime"
					);
			Memento::sendHTTPError( 405, $header, null );
			exit();
		}

		$waddress = str_replace( '$1', '', $wgArticlePath );

		// getting the title of the page from the request uri
		//$title = str_replace( $wgServer . $waddress, "", $par );
		$title = substr( $par, strlen( $wgServer . $waddress ) );

		$page_namespace_id = 0;

		$objTitle = Title::newFromText( $title );
		$page_namespace_id = $objTitle->getNamespace();

		if ( in_array( $page_namespace_id, $wgMementoExcludeNamespaces ) ) {
			$msg = wfMessage( 'timegate-404-inaccessible', $par )->text();
			Memento::sendHTTPError( 404, null, $msg );
			exit();
		}

		$pg_id = $objTitle->getArticleID();

		$new_title = $objTitle->getPrefixedURL();
		$new_title = urlencode( $new_title );

		if ( $pg_id > 0 ) {
			$this->getMementoForResource( $pg_id, $new_title );
		}
		else {
			$msg = wfMessage( 'timegate-404-title', $new_title )->text();
			$header = array( "Vary" => "negotiate, accept-datetime" );

			Memento::sendHTTPError( 404, $header, $msg );
			exit();
		}
	}


	/**
	 * Checks the validity of the requested datetime in the
	 * accept-datetime header. Throws a 400 HTTP error if the
	 * requested dt is not parseable. Also sends first and last
	 * memento link headers as additional information with the errors.
	 *
	 * @param: $first: associative array, not optional.
	 *	  url and dt of the first memento.
	 * @param: $last: associative array, not optional.
	 *	  url and dt of the last memento.
	 * @param: $Link: String, not optional.
	 *	   A string in link header format containing the
	 *	   original, timemap, timegate, etc links.
	 */

	function parseRequestDateTime( $first, $last, $Link ) {

		$request = $this->getRequest();

		// getting the datetime from the http header
		$raw_dt = $request->getHeader( "ACCEPT-DATETIME" );

		// looks for datetime enclosed in ""
		$req_dt = str_replace( '"', '', $raw_dt );

		// validating date time...
		$dt = wfTimestamp( TS_MW, $req_dt );

		if ( !$dt ) {
			$msg = wfMessage( 'timegate-400-date', $req_dt )->text();

			$msg .= wfMessage( 'timegate-400-first-memento', $first['uri'] )->text();
			$msg .= wfMessage( 'timegate-400-last-memento', $last['uri'] )->text();

			$header = array( "Link" => Memento::constructLinkHeader( $first, $last ) . $Link );
			Memento::sendHTTPError( 400, $header, $msg );
			exit();
		}
		return array( $dt, $raw_dt );
	}




	/**
	 * This function retrieves the appropriate revision for a resource
	 * and builds and sends the memento headers.
	 *
	 * @param: $pg_id: number, not optional.
	 *	  The valid page_id of the requested resource.
	 * @param: $title: String, not optional.
	 *	  The title value of the requested resource.
	 */

	function getMementoForResource( $pg_id, $title ) {

		global $wgArticlePath;
		$request = $this->getRequest();

		$waddress = str_replace( '/$1', '', $wgArticlePath );

		// creating a db object to retrieve the old revision id from the db.
		$dbr = wfGetDB( DB_SLAVE );

		$alt_header = '';
		$last = array();
		$first = array();
		$next = array();
		$prev = array();
		$mem = array();

		$db_details = array( 'title' => $title, 'waddress' => $waddress );

		// first/last version
		$last = Memento::getMementoFromDb( 'last', $pg_id, null, $db_details );
		$first = Memento::getMementoFromDb( 'first', $pg_id, null, $db_details );

		$Link = "<" . wfExpandUrl( $waddress . "/". $title ) . ">; rel=\"original latest-version\", ";
		$Link .= "<" .
			wfExpandUrl( $waddress . "/" . SpecialPage::getTitleFor('TimeMap') )
			. "/" .
			wfExpandUrl( $waddress . "/" . $title) .
			">; rel=\"timemap\"; type=\"application/link-format\"";

		// checking for the occurance of the accept datetime header.
		if ( !$request->getHeader( 'ACCEPT-DATETIME' ) ) {

			if ( isset( $last['uri'] ) ) {
				$memuri = $last['uri'];
				$mem = $last;
			}
			else {
				$memuri = $first['uri'];
				$mem = $first;
			}

			$prev = Memento::getMementoFromDb( 'prev', $pg_id, null, $db_details );

			$header = array(
					"Location" => $memuri,
					"Vary" => "negotiate, accept-datetime",
					"Link" => Memento::constructLinkHeader( $first, $last, $mem, '', $prev ) . $Link
					);

			Memento::sendHTTPError( 302, $header, null );
			exit();
		}

		list( $dt, $raw_dt ) = $this->parseRequestDateTime( $first, $last, $Link );

		// if the requested time is earlier than the first memento,
		// the first memento will be returned
		// if the requested time is past the last memento, or in the future,
		// the last memento will be returned.
		if ( $dt < wfTimestamp( TS_MW, $first['dt'] ) ) {
			$dt = wfTimestamp( TS_MW, $first['dt'] );
		}
		elseif ( $dt > wfTimestamp( TS_MW, $last['dt'] ) ) {
			$dt = wfTimestamp( TS_MW, $last['dt'] );
		}

		$prev = Memento::getMementoFromDb( 'prev', $pg_id, $dt, $db_details );
		$next = Memento::getMementoFromDb( 'next', $pg_id, $dt, $db_details );
		$mem = Memento::getMementoFromDb( 'memento', $pg_id, $dt, $db_details );

		$header = array(
				"Location" => $mem['uri'],
				"Vary" => "negotiate, accept-datetime",
				"Link" => Memento::constructLinkHeader( $first, $last, $mem, $next, $prev ) . $Link
				);

		Memento::sendHTTPError( 302, $header, null );
		exit();
	}
}
