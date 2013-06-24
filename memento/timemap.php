<?php

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
	 * Constructor
	 */
	function __construct() {
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
	 * @param: $par: String.
	 *	  The title parameter that mediawiki returns.
	 */

	function execute( $par ) {

		global $wgArticlePath;
		global $wgServer;
		global $wgTimemapNumberOfMementos;
		global $wgMementoExcludeNamespaces;

		if (!is_array( $wgMementoExcludeNamespaces )) {
			$wgMementoExcludeNamespaces = array();
		}

		$request = $this->getRequest();
		$requestURL = $request->getRequestURL();
		$this->setHeaders();

		if ( !$par ) {
			return;
		}

		// getting the title of the page from the request uri
		$waddress = str_replace( '$1', '', $wgArticlePath );

		$tmRevTS = false;
		$tmDir = "next";

		if (isset( $wgTimemapNumberOfMementos )) {
			$wgTimeMapNumberOfMementos = $wgTimemapNumberOfMementos + 1;
		} else {
			$wgTimeMapNumberOfMementos = 501;
		}

		if ( stripos( $par, $wgServer.$waddress ) == 0 ) {
			$title = str_replace( $wgServer . $waddress, "", $par );
		}
		elseif ( stripos( $par, $wgServer. $waddress ) > 0 ) {
			$titleParts = explode( $wgServer.$waddress, $par );

			if ( isset( $titleParts[1] ) ) {
				$title = $titleParts[1];
			}
			else {
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
					$tmDir = ( $arrayParams[1] > 0 ) ? "next" : "prev";
				}
			}
		}

		$waddress = str_replace( '/$1', '', $wgArticlePath );

		if ( !$title ) {
			$msg = wfMessage( 'timemap-404-title', $par )->text();
			Memento::sendHTTPError( 404, null, $msg );
			exit();
		}
		else {
			$objTitle = Title::newFromText( $title );
		}

		if ( in_array( $objTitle->getNamespace(), $wgMementoExcludeNamespaces ) ) {
			$msg = wfMessage( 'timemap-404-inaccessible', $par );
			Memento::sendHTTPError( 404, null, $msg );
			exit();
		}

		$pg_id = $objTitle->getArticleID();
		$title = $objTitle->getPrefixedURL();

		$title = urlencode( $title );

		$splPageTimemapName = SpecialPage::getTitleFor( 'TimeMap' )->getPrefixedText();

		if ( $pg_id > 0 ) {
			// creating a db object to retrieve the old revision id from the db.
			$dbr = wfGetDB( DB_SLAVE );

			$wikiaddr = wfExpandUrl( $waddress . "/" . $title );
			$requri = wfExpandUrl( $wgServer . $requestURL );

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
