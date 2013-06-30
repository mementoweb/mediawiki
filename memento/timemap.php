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

# ensure that the script can't be executed outside of Mediawiki
if ( ! defined( 'MEDIAWIKI' ) ) {
	echo "Not a valid entry point";
	exit( 1 );
}

/**
 *
 * A Memento TimeMap. See http://mementoweb.org
 *
 */
class TimeMap extends SpecialPage
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
	 * @var string $numberOfMementos: the number of mementos to return 
	 */
	private $numberOfMementos;

	/**
	 * Constructor
	 */
	function __construct() {
		global $wgArticlePath;
		global $wgServer;
		global $wgMementoTimemapNumberOfMementos;
		global $wgMementoExcludeNamespaces;

		$this->excludeNamespaces = $wgMementoExcludeNamespaces;
		$this->server = $wgServer;
		$this->articlePath = $wgArticlePath;
		$this->numberOfMementos = $wgMementoTimemapNumberOfMementos;

		parent::__construct( "TimeMap" );
	}

	/**
	 * The init function that is called by mediawiki when loading
	 * this SpecialPage.
	 * The parameter passed to this function is the original uri.
	 * This function verifies if the article requested is valid and accessible,
	 * and fetches it's page_id, title object and the number of revisions needed
	 * to construct the timemap. The maximum number of revisions in a timemap is
	 * indicated as a config parameter.
	 *
	 * The typical TimeMap URL for Mediawiki looks like:
	 * a) http://<wiki_name>/Special:TimeMap/http://<wiki_name>/<Title>
	 * b) http://<wiki_name>/Special:TimeMap/<TimeStamp>/<TimeMap_Direction>/http://<wiki_name>/<Title>
	 * 
	 * Case (a) is the straight forward one, and is handled in line 93.
	 * 
	 * Case (b):
	 * The TimeStamp and the direction here is for partial timemaps. We can
	 * specify a time in YYYYMMDDHHMMSS and the direction as 1 (asc, going
	 * forward in time) and -1 (desc, backwards). 
	 * 
	 *
	 * @param: $par: String.
	 *	  The title parameter that mediawiki returns.
	 */
	function execute( $par ) {

		$articlePath = $this->articlePath;
		$server = $this->server;
		$numberOfMementos = $this->numberOfMementos;
		$excludeNamespaces = $this->excludeNamespaces;

		if (!is_array( $excludeNamespaces )) {
			$excludeNamespaces = array();
		}

		$request = $this->getRequest();
		$requestURL = $request->getRequestURL();
		$this->setHeaders();

		if ( !$par ) {
			// TODO:  TimeMap welcome message should be displayed here
			return;
		}

		// getting the title of the page from the request uri
		$waddress = str_replace( '$1', '', $articlePath );

		$tmRevTS = false;
		$tmDir = "next";

		if (isset( $numberOfMementos )) {
			$tmSize = $numberOfMementos;
		} else {
			$tmSize = 500;
		}

		if ( stripos( $par, $server.$waddress ) == 0 ) {
			$title = str_replace( $server . $waddress, "", $par );
		}
		elseif ( stripos( $par, $server. $waddress ) > 0 ) {
			$titleParts = explode( $server.$waddress, $par );

			if ( isset( $titleParts[1] ) ) {
				$title = $titleParts[1];
			}
			else {
				// could not get Title from the TimeMap URL if pagination used
				$msg = wfMessage( 'timemap-404-title', $par )->text();
				Memento::sendHTTPError( 404, null, $msg );
				exit();
			}

			if ( isset( $titleParts[0] ) ) {
				$arrayParams = explode( '/', $titleParts[0] );

				if ( isset( $arrayParams[0] ) ) {
					$tmRevTS = wfTimestamp( TS_MW, $arrayParams[0] );
					if ( !$tmRevTS ) {
						$msg = wfMessage( 'timemap-404-title', $par )->text();
						Memento::sendHTTPError( 404, null, $msg );
						exit();
					}
				}

				if ( isset( $arrayParams[1] ) ) {
					// determining the direction of the pagination
					$tmDir = ( $arrayParams[1] > 0 ) ? "next" : "prev";
				}
			}
		}

		$waddress = str_replace( '/$1', '', $articlePath );

		if ( !$title ) {
			$msg = wfMessage( 'timemap-404-title', $par )->text();
			Memento::sendHTTPError( 404, null, $msg );
			exit();
		}
		else {
			// using the title retrieved to create a Mediawiki Title object
			$objTitle = Title::newFromText( $title );
		}

		$namespace = $objTitle->getNamespace();
		//echo "Namespace is [$namespace]";

		if ( in_array( $objTitle->getNamespace(), $excludeNamespaces ) ) {
			$msg = wfMessage( 'timemap-404-inaccessible', $par );
			Memento::sendHTTPError( 404, null, $msg );
			exit();
		}

		$pg_id = $objTitle->getArticleID();

		// any value requested by user is over-written by value from database:
		// the standardized title for the request
		$title = $objTitle->getPrefixedURL();

		$title = urlencode( $title );

		$splPageTimemapName = SpecialPage::getTitleFor( 'TimeMap' )->getPrefixedText();

		if ( $pg_id > 0 ) {
			// creating a db object to retrieve the old revision id from the db.
			$dbr = wfGetDB( DB_SLAVE );

			$wikiaddr = wfExpandUrl( $waddress . "/" . $title );
			$requri = wfExpandUrl( $server . $requestURL );

			// querying the database and building info for the link header.
			if ( !$tmRevTS ) {
				$xares = $dbr->select(
						"revision",
						array( 'rev_id', 'rev_timestamp' ),
						array( "rev_page" => $pg_id ),
						__METHOD__,
						array(
							"ORDER BY" => "rev_timestamp DESC",
							"LIMIT" => $tmSize
							)
						);
			}
			elseif ( $tmDir == 'next' ) {
				$xares = $dbr->select(
						"revision",
						array( 'rev_id', 'rev_timestamp' ),
						array(
							"rev_page" => $pg_id,
							"rev_timestamp>" . $dbr->addQuotes( $tmRevTS )
							),
						__METHOD__,
						array(
							"ORDER BY" => "rev_timestamp DESC",
							"LIMIT" => $tmSize
							)
						);
			}
			else {
				$xares = $dbr->select(
						"revision",
						array( 'rev_id', 'rev_timestamp' ),
						array(
							"rev_page" => $pg_id,
							"rev_timestamp<" . $dbr->addQuotes( $tmRevTS )
							),
						__METHOD__,
						array(
							"ORDER BY" => "rev_timestamp DESC",
							"LIMIT" => "$tmSize"
							)
						);
			}

			foreach ( $xares as $xarow ) {
				$revTS[] = $xarow->rev_timestamp;
				$revID[] = $xarow->rev_id;
			}

			$cnt = count( $revTS );

			$orgTmUri = wfExpandUrl( $waddress ) ."/".
				$splPageTimemapName . "/" . $wikiaddr;

			$timegate = str_replace(
				$splPageTimemapName,
				SpecialPage::getTitleFor( 'TimeGate' ),
				$orgTmUri
				);

			$header = array(
				"Content-Type" => "application/link-format;charset=UTF-8",
				"Link" => "<" . $requri . ">; " .
				"anchor=\"" . $wikiaddr . "\"; " .
				"rel=\"timemap\"; type=\"application/link-format\""
				);

			Memento::sendHTTPError( 200, $header, null );

			echo "<" . $timegate . ">;rel=\"timegate\", \n";
			echo "<" . $requri . ">;" .
				"rel=\"self\";from=\"" .
				wfTimestamp( TS_RFC2822, $revTS[$cnt - 2] ) .
				"\";until=\"" .
				wfTimestamp( TS_RFC2822, $revTS[0] ) . "\", \n";

			if ( $tmRevTS ) {
				// fetching the timestamp for "until" attribute.
				$resnext = $dbr->select(
						"revision",
						array( 'rev_timestamp' ),
						array(
							"rev_page" => $pg_id,
							"rev_timestamp>" . $dbr->addQuotes( $revTS[0] )
							),
						__METHOD__,
						array( "LIMIT" => "1" )
						);

				$revNextFirstTS = '';

				if( $revNext = $dbr->fetchObject( $resnext ) ) {
					$revNextFirstTS = $revNext->rev_timestamp;
				}

				// link to the next timemap
				if( $revNextFirstTS )
					echo "<" . wfExpandUrl( $waddress ) . "/" .
					$splPageTimemapName . "/" .
					$revTS[0] . "/1/" . $wikiaddr .
					">;rel=\"timemap\";from=\"" .
					wfTimestamp( TS_RFC2822, $revNextFirstTS ) .
					"\", \n";
			}

			// prev timemap link
			if ( $cnt == $tmSize )
				echo "<" .
				wfExpandUrl( $waddress ) . "/" .
				$splPageTimemapName . "/" .
				$revTS[$cnt - 2] .
				"/-1/" . $wikiaddr .
				">;rel=\"timemap\";until=\"" .
				wfTimestamp( TS_RFC2822, $revTS[$cnt - 1] ) ."\", \n";

			echo "<" . $wikiaddr . ">;rel=\"original latest-version\", \n";

			for ( $i = $cnt - 2; $i > 0; $i-- ) {
				$uri = wfAppendQuery(
					wfExpandUrl( $waddress ),
					array( "title" => $title, "oldid" => $revID[$i] )
					);

				echo "<" . $uri . ">;rel=\"memento\";datetime=\"" .
					wfTimestamp( TS_RFC2822,  $revTS[$i] ) . "\", \n";
			}

			$uri = wfAppendQuery(
				wfExpandUrl( $waddress ),
				array(
					"title" => $title, "oldid" => $revID[0] )
					);

			echo "<" . $uri . ">;rel=\"memento\";datetime=\"" .
				wfTimestamp( TS_RFC2822,  $revTS[0] ) . "\"";

			exit();
		}
		else {
			$msg = wfMsgForContent( 'timemap-404-title', $title );
			Memento::sendHTTPError( 404, null, $msg );
			exit();
		}
	}
}
