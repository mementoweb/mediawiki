<?php
/**
 * This file is part of the Memento Extension to MediaWiki
 * http://www.mediawiki.org/wiki/Extension:Memento
 *
 * @section LICENSE
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 * 
 * @file
 */

// ensure that the script can't be executed outside of Mediawiki
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
	 * @var string $articlePath: the base URL for all other links
	 */
	private $articlePath;

	/**
	 * @var string $server:  the base URL of the server
	 */
	private $server;

	/**
	 * @var array excludeNamespaces: the namespaces to exclude from TimeGates
	 */
	private $excludeNamespaces;

	/**
	 * Constructor
	 */
	function __construct() {
		global $wgArticlePath;
		global $wgServer;
		global $wgMementoExcludeNamespaces;

		parent::__construct( "TimeGate" );

		$this->articlePath = $wgArticlePath;
		$this->server = $wgServer;

		if (!is_array( $wgMementoExcludeNamespaces )) {
			$this->excludeNamespaces = array();
		} else {
			$this->excludeNamespaces = $wgMementoExcludeNamespaces;
		}
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
		$articlePath = $this->articlePath;
		$server = $this->server;
		$excludeNamespaces = $this->excludeNamespaces;
		$requestMethod = $this->getRequest()->getMethod();

		if (!is_array( $excludeNamespaces )) {
			$excludeNamespaces = array();
		}

		$this->setHeaders();

		$requestURL = $request->getRequestURL();
		$mementoResponse = $request->response();

		if ( !$par ) {
			// TODO:  change the welcome message to something more
			// friendly and useful
			$out->addHTML( wfMessage( 'timegate-welcome-message' )->parse() );
			return;
		}

		if ( $requestMethod != 'GET' && $requestMethod != 'HEAD' ) {
			$header = array(
					"Allow" => "GET, HEAD",
					"Vary" => "negotiate, accept-datetime"
					);
			$titlemsg = 'timegate';
			$textmsg = 'timegate-405-badmethod';
			$params = array();
			Memento::sendHTTPResponse(
				$out, $mementoResponse, 405, $header,
				$textmsg, $params, $titlemsg
				);
		} else {

			$waddress = str_replace( '$1', '', $articlePath );

			// getting the title of the page from the request uri
			//$title = str_replace( $wgServer . $waddress, "", $par );
			$title = substr( $par, strlen( $server . $waddress ) );

			$page_namespace_id = 0;

			$objTitle = Title::newFromText( $title );
			$page_namespace_id = $objTitle->getNamespace();

			if ( in_array( $page_namespace_id, $excludeNamespaces ) ) {
				$titlemsg = 'timegate';
				$textmsg = 'timegate-404-inaccessible';
				$params = array( $par );
				Memento::sendHTTPResponse(
					$out, $mementoResponse, 404, $header,
					$textmsg, $params, $titlemsg
					);
			} else {

				$pg_id = $objTitle->getArticleID();

				$new_title = $objTitle->getPrefixedURL();
				$new_title = urlencode( $new_title );

				if ( $pg_id > 0 ) {
					$this->getMementoForResource( $pg_id, $new_title );
				}
				else {
					$header = array( "Vary" => "negotiate, accept-datetime" );
					$titlemsg = 'timegate';
					$textmsg = 'timegate-404-title';
					$params = array( $new_title );
					Memento::sendHTTPResponse(
						$out, $mementoResponse, 404, $header,
						$textmsg, $params, $titlemsg
						);
				}
			}
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
		$out = $this->getOutput();
		$mementoResponse = $this->getRequest()->response();

		// getting the datetime from the http header
		$raw_dt = $request->getHeader( "ACCEPT-DATETIME" );

		// looks for datetime enclosed in ""
		$req_dt = str_replace( '"', '', $raw_dt );

		// validating date time...
		$dt = wfTimestamp( TS_MW, $req_dt );

		if ( !$dt ) {

			$header = array( "Link" => Memento::constructLinkHeader( $first, $last ) . $Link );

			$titlemsg = 'timegate';
			$textmsg = 'timegate-400-date';
			$params = array( $req_dt, $first['uri'], $last['uri'] );

			Memento::sendHTTPResponse(
				$out, $mementoResponse, 400, $header,
				$textmsg, $params, $titlemsg
				);

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

		$articlePath = $this->articlePath;
		$request = $this->getRequest();
		$outputPage = $this->getOutput();
		$mementoResponse = $this->getRequest()->response();

		$waddress = str_replace( '/$1', '', $articlePath );

		// creating a db object to retrieve the old revision id from the db.
		$dbr = wfGetDB( DB_SLAVE );

		$alt_header = '';

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

			Memento::sendHTTPResponse(
				$outputPage, $mementoResponse, 302, $header,
				null
				);
		}

		list( $dt, $raw_dt ) = $this->parseRequestDateTime( $first, $last, $Link );

		// if we don't get a $dt back, then a 400 should have been queued up
		if ($dt) {

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

			Memento::sendHTTPResponse(
				$outputPage, $mementoResponse, 302, $header,
				null
				);
		}
	}
}
