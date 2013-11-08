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

/**
 * Ensure that this file is only executed in the right context.
 *
 * @see http://www.mediawiki.org/wiki/Security_for_developers
 */
if ( ! defined( 'MEDIAWIKI' ) ) {
	echo "Not a valid entry point";
	exit( 1 );
}

/**
 * This class is the exception used by all MementoResource types.
 *
 * The large number of getters exist mainly to conform to the standard
 * set by the PHP built-in exception class.
 *
 */
class MementoResourceException extends Exception {

	/**
	 * @var string $statusCode - intended HTTP status code
	 */
	private $statusCode;

	/**
	 * @var Response object $response - response object from throwing code
	 */
	private $response;

	/**
	 * @var OutputPage object $output - OutputPage object from throwing code
	 */
	private $outputPage;

	/**
	 * @var string $textMessage - the full text to display to the user
	 */
	private $textMessage;

	/**
	 * @var string $titleMessage - the title text to display to the user
	 */
	private $titleMessage;

	/**
	 * redefined constructor for our purposes
	 *
	 * @param $textMessage - message key (string) for page text
	 * @param $titleMessage - message key (string) for page title
	 * @param $outputPage - OutputPage object from this session
	 * @param $response	- response object from this session
	 * @param $statusCode - the HTTP status code to use in the response
	 * @param $params - parameters for the $textMessage
	 *
	 */
	public function __construct(
		$textMessage, $titleMessage, $outputPage, $response, $statusCode,
		$params = array()) {

		$this->statusCode = $statusCode;
		$this->response = $response;
		$this->outputPage = $outputPage;
		$this->textMessage = $textMessage;
		$this->titleMessage = $titleMessage;
		$this->params = $params;

		parent::__construct($textMessage, $statusCode, null);
	}

	/**
	 * custom string representation of object (for testing)
	 */
	public function __toString() {
		return __CLASS__ . ":[{$this->statusCode}]: {$this->textMessage}\n";
	}

	/**
	 * getter for StatusCode
	 *
	 * @return $statusCode
	 */
	public function getStatusCode() {
		return $this->statusCode;
	}

	/**
	 * getter for response object
	 *
	 * @return Response Object for this session
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * getter for outputPage
	 *
	 * @return OutputPage Object for this session
	 */
	public function getOutputPage() {
		return $this->outputPage;
	}

	/**
	 * getter for textMessage
	 *
	 * @return message key (string) for page text
	 */
	public function getTextMessage() {
		return $this->textMessage;
	}

	/**
	 * getter for titleMessage
	 *
	 * @return message key (string) for page title
	 */
	public function getTitleMessage() {
		return $this->titleMessage;
	}

	/**
	 * getter for params
	 *
	 * @return message parameters for page text message
	 */
	public function getParams() {
		return $this->params;
	}

}

/**
 * This abstract class is the parent of all MementoResource types.
 * As such, it contains the methods used by all of the Memento Pages.
 * 
 */
abstract class MementoResource {

	/**
	 * @var object $conf: configuration object for Memento Extension
	 */
	protected $conf;

	/**
	 * @var object $dbr: DatabaseBase object for Memento Extension
	 */
	protected $dbr;

	/**
	 * @var string $mwrelurl: Base relative URL for Mediawiki installation
	 */
	protected $mwrelurl;

	/**
	 * @var $article - Article Object of this Resource
	 */
	protected $article;

	/**
	 * @var $mementoOldID - timestamp of the Memento
	 */
	protected $mementoOldID;

	/**
	 * getArticleObject
	 *
	 * Getter for Article Object used in constructor.
	 *
	 * @return Article $article
	 */
	public function getArticleObject() {
		return $this->article;
	}

	/**
	 * getConfig
	 *
	 * Getter for MementoConfig object used in constructor.
	 *
	 * @return MementoConfig $config
	 */
	public function getConfig() {
		return $this->conf;
	}

	/**
	 * fetchMementoFromDatabase
	 *
	 * Make the actual database call.
	 *
	 * @param $sqlCondition - the conditional statement
	 * @param $sqlOrder - order of the data returned (e.g. ASC, DESC)
	 *
	 * @return $revision - associative array with id and timestamp keys
	 */
	public function fetchMementoFromDatabase( $sqlCondition, $sqlOrder ) {

		$dbr = $this->dbr;

		$results = $dbr->select(
			'revision',
			array( 'rev_id', 'rev_timestamp'),
			$sqlCondition,
			__METHOD__,
			array( 'ORDER BY' => $sqlOrder, 'LIMIT' => '1' )
			);

		$row = $dbr->fetchObject( $results );

		$revision = array();

		if ($row) {
			$revision['id'] = $row->rev_id;
			$revision['timestamp'] = wfTimestamp(
				TS_RFC2822, $row->rev_timestamp );
		}

		return $revision;

	}

	/**
	 * getFirstMemento
	 *
	 * Extract the first memento from the database.
	 *
	 * @param $title - title object
	 *
	 * @return $revision - associative array with id and timestamp keys
	 */
	public function getFirstMemento( $title ) {
		$revision = array();

		$firstRevision = $title->getFirstRevision();

		$revision['timestamp'] = 
			wfTimestamp( TS_RFC2822, $firstRevision->getTimestamp());
		$revision['id'] = $firstRevision->getId();

		return $revision;
	}

	/**
	 * getLastMemento
	 *
	 * Extract the last memento from the database.
	 *
	 * @param $title - title object
	 *
	 * @return $revision - associative array with id and timestamp keys
	 */
	public function getLastMemento( $title ) {

		$revision = array();

		$lastRevision = WikiPage::factory( $title )->getRevision();

		$revision['timestamp'] =
			wfTimestamp( TS_RFC2822, $lastRevision->getTimestamp());
		$revision['id'] = $lastRevision->getId();

		return $revision;
	}

	/**
	 * getCurrentMemento
	 *
	 * Extract the memento that best matches from the database.
	 *
	 * @param $dbr - DatabaseBase object
	 * @param $pageID - page identifier
	 * @param $pageTimestamp - timestamp used for finding the last memento
	 *
	 * @return $revision - associative array with id and timestamp keys
	 */
	public function getCurrentMemento( $pageID, $pageTimestamp ) {

		$dbr = $this->dbr;

		$sqlCondition =
			array(
				'rev_page' => $pageID,
				'rev_timestamp<=' . $dbr->addQuotes( $pageTimestamp )
				);
		$sqlOrder = 'rev_timestamp DESC';

		return $this->fetchMementoFromDatabase(
			$sqlCondition, $sqlOrder );
	}

	/**
	 * getNextMemento
	 *
	 * Extract the last memento from the database.
	 *
	 * @param $pageID - page identifier
	 * @param $pageTimestamp - timestamp used for finding the last memento
	 *
	 * @return $revision - associative array with id and timestamp keys
	 */
	public function getNextMemento( $pageID, $pageTimestamp ) {

		$dbr = $this->dbr;

		$sqlCondition =
			array(
				'rev_page' => $pageID,
				'rev_timestamp>' . $dbr->addQuotes( $pageTimestamp )
				);
		$sqlOrder = 'rev_timestamp ASC';

		return $this->fetchMementoFromDatabase(
			$sqlCondition, $sqlOrder );
	}

	/**
	 * getPrevMemento
	 *
	 * Extract the last memento from the database.
	 *
	 * @param $pageID - page identifier
	 * @param $pageTimestamp - timestamp used for finding the last memento
	 *
	 * @return $revision - associative array with id and timestamp keys
	 */
	public function getPrevMemento( $pageID, $pageTimestamp ) {

		$dbr = $this->dbr;

		$sqlCondition =
			array(
				'rev_page' => $pageID,
				'rev_timestamp<' . $dbr->addQuotes( $pageTimestamp )
				);
		$sqlOrder = 'rev_timestamp DESC';

		return $this->fetchMementoFromDatabase(
			$sqlCondition, $sqlOrder );
	}

	/**
	 * getFullURIForID
	 *
	 * @param $id - ID of page
	 * @param $title - article title
	 *
	 * @return $fullURI - full URI referring to article and revision
	 */
	public function getFullURIForID( $id, $title ) {

		$scriptPath = $this->mwrelurl;

		return wfAppendQuery(
			wfExpandUrl( $scriptPath ),
			array( 'title' => $title, 'oldid' => $id )
			);

	}

	/**
	 * parseRequestDateTime
	 *
	 * Take in the RFC2822 datetime and convert it to the format used by
	 * Mediawiki.
	 *
	 * @param $requestDateTime
	 *
	 * @return $dt - datetime in mediawiki database format
	 */
	public function parseRequestDateTime( $requestDateTime ) {

		$req_dt = str_replace( '"', '', $requestDateTime );

		$dt = wfTimestamp( TS_MW, $req_dt );

		return $dt;
	}

	/**
	 * chooseBestTimestamp
	 *
	 * If the requested time is earlier than the first memento,
	 * the first memento will be returned.
	 * If the requested time is past the last memento, or in the future,
	 * the last memento will be returned.
	 * Otherwise, go with the one we've got because the future database call
	 * will get the nearest memento.
	 *
	 * @param $firstTimestamp - the first timestamp for which we have a memento
	 *				formatted in the TS_MW format
	 * @param $lastTimestamp - the last timestamp for which we have a memento
	 * @param $givenTimestamp - the timestamp given by the request header
	 *
	 * @return $chosenTimestamp - the timestamp to use
	 */
	public function chooseBestTimestamp(
		$firstTimestamp, $lastTimestamp, $givenTimestamp ) {

		$firstTimestamp = wfTimestamp( TS_MW, $firstTimestamp );
		$lastTimestamp = wfTimestamp( TS_MW, $lastTimestamp );

		$chosenTimestamp = null;

		if ( $givenTimestamp < $firstTimestamp ) {
			$chosenTimestamp = $firstTimestamp;
		} elseif ( $givenTimestamp > $lastTimestamp ) {
			$chosenTimestamp = $lastTimestamp;
		} else {
			$chosenTimestamp = $givenTimestamp;
		}

		return $chosenTimestamp;
	}

	/**
	 * constructMementoLinkHeaderEntry
	 *
	 * This creates the entry for a memento for the Link Header.
	 *
	 * @param $title - the title string of the given page
	 * @param $id - the oldid of the given page
	 * @param $timestamp - the timestamp of this Memento
	 * @param $relation - the relation type of this Memento
	 *
	 * @return $entry - full Memento Link header entry
	 */
	public function constructMementoLinkHeaderEntry(
		$title, $id, $timestamp, $relation ) {

		$url = $this->getFullURIForID( $id, $title );

		$entry = '<' . $url . '>; rel="' . $relation . '"; datetime="' .
			$timestamp . '"';

		return $entry;

	}

	/**
	 * constructTimeMapLinkHeaderWithBounds
	 *
	 * This creates the entry for timemap in the Link Header.
	 *
	 * @param $title - the title string of the given page
	 * @param $from - the from timestamp for the TimeMap
	 * @param $until - the until timestamp for the TimeMap
	 *
	 * @return $entry - full Memento TimeMap relation with from and until
	 */
	public function constructTimeMapLinkHeaderWithBounds(
		$title, $from, $until ) {

		$entry = $this->constructTimeMapLinkHeader( $title );

		$entry .= "; from=\"$from\"; until=\"$until\"";

		return $entry;
	}

	/**
	 * constructTimeMapLinkHeader
	 *
	 * This creates the entry for timemap in the Link Header.
	 *
	 * @param $title - the title string of the given page
	 *
	 * @return $entry - Memento TimeMap relation
	 */
	public function constructTimeMapLinkHeader( $title ) {

		$scriptUrl = $this->mwrelurl;

		$title = rawurlencode($title);

		$entry = '<' .
			wfExpandUrl(
				$scriptUrl . '/' . SpecialPage::getTitleFor( 'TimeMap' )
				) . '/' . $title .
			'>; rel="timemap"; type="application/link-format"';

		return $entry;
	}

	/**
	 * getSafelyFormedURI
	 *
	 * This function uses wfExpandUrl to safely form the URI for the given
	 * page title string.
	 *
	 * @param $title - the title string of the given page
	 *
	 * @return $safeURI - the safely formed URI
	 */
	public function getSafelyFormedURI( $title ) {

		$scriptUrl = $this->mwrelurl;

		$title = rawurlencode($title);

		$safeURI = wfExpandUrl( $scriptUrl . '/' . $title );

		return $safeURI;
	}

	/**
	 * getFullNamespacePageTitle
	 * 
	 * This function returns the namespace:title string from the URI
	 * corresponding to this resource.
	 *
	 * @param $titleObj - title object corresponding to this resource
	 *
	 * @return $title - the namespace:title string for the given page
	 */
	public function getFullNamespacePageTitle( $titleObj ) {
		$title = $titleObj->getDBkey();
		$namespace = $titleObj->getNsText();

		if ( $namespace ) {
			$title = "$namespace:$title";
		}

		return $title;
	}

	/**
	 * constructLinkRelationHeader
	 *
	 * This creates a link header entry for the given URI, with no
	 * extra information, just URL and relation.
	 *
	 * @param $url
	 * @param $relation
	 *
	 * @return relation string
	 */
	public function constructLinkRelationHeader( $url, $relation ) {
		return '<' . $url . '>; rel="' . $relation . '"';
	}

	/**
	 * generateRecommendedLinkHeaderRelations
	 *
	 * This function generates the recommended link header relations,
	 * handling cases such as 'first memento' and 'last memento' vs.
	 * 'first last memento', etc.
	 *
	 * @param $title - the article title text part of the URI
	 * @param $first - associative array containing info on the first memento
	 * 					with the keys 'timestamp' and 'id'
	 * @param $last	- associative array containing info on the last memento
	 * 					with the keys 'timestamp' and 'id'
	 *
	 * @return $linkRelations - array of link relations
	 */
	public function generateRecommendedLinkHeaderRelations(
		$title, $first, $last ) {

		$linkRelations = array();

		$entry = $this->constructTimeMapLinkHeaderWithBounds(
			$title, $first['timestamp'], $last['timestamp'] );
		array_push( $linkRelations, $entry );

		if ( $first['id'] == $last['id'] ) {
			$entry = $this->constructMementoLinkHeaderEntry(
				$title, $first['id'], $first['timestamp'],
				'first last memento' );
			array_push( $linkRelations, $entry );
		} else {
			$entry = $this->constructMementoLinkHeaderEntry(
				$title, $first['id'], $first['timestamp'],
				'first memento' );
			array_push( $linkRelations, $entry );
			$entry = $this->constructMementoLinkHeaderEntry(
				$title, $last['id'], $last['timestamp'],
				'last memento' );
			array_push( $linkRelations, $entry );
		}

		return $linkRelations;
	}

	/**
	 * setMementoTimestamp
	 *
	 * Set the Memento Timestamp for future calls.
	 *
	 * @param $timestamp - the timestamp to set
	 */
	public function setMementoOldID( $id ) {
		$this->mementoOldID = $id;
	}

	/**
	 * getMementoTimestamp
	 *
	 * Get the Memento Timestamp
	 *
	 * @return $this->mementoOldID - the OldID stored previously
	 */
	public function getMementoOldID() {
		return $this->mementoOldID;
	}

	/**
	 * renderError
	 *
	 * Render error page.  This is only used for 40* and 50* HTTP statuses.
	 * This function is static so it can be called in cases where we have
	 * no MementoResource object.
	 *
	 * @param $out - OutputPage object
	 * @param $error - MementoResourceException object
	 * @param $errorPageType - the error page type 'traditional' or 'friendly'
	 *
	 */
	public static function renderError($out, $error, $errorPageType) {
		if ( $errorPageType == 'traditional' ) {

			$msg = wfMessage(
				$error->getTextMessage(), $error->getParams()
				)->text();

			$error->getResponse()->header(
				"HTTP", true, $error->getStatusCode());

			echo $msg;

			$out->disable();
		} else {

			$out->showErrorPage(
				$error->getTitleMessage(),
				$error->getTextMessage(),
				$error->getParams()
				);
		}
	}

	/**
	 * mementoPageResourceFactory
	 *
	 * A factory for creating the correct MementoPageResource type.
	 *
	 * @param $conf - MementoConfig object, passed to constructor
	 * @param $dbr - DatabaseBase object, passed to constructor
	 * @param $oldID - string indicating revision ID
	 *		used in decision
	 *
	 * @return $resource - the correct instance of MementoResource based
	 *						on current conditions
	 */
	public static function mementoPageResourceFactory(
		$conf, $dbr, $article, $oldID, $request ) {

		$resource = null;

		if ( $oldID == 0 ) {

			if ( $request->getHeader('ACCEPT-DATETIME') ) {

				if ( $conf->get('Negotiation') == "200" ) {
					/* we are requesting a Memento, but via 200-style
						Time Negotiation */
					$resource = new MementoResourceFrom200TimeNegotiation(
						$conf, $dbr, $article );

				} else {
					/* we are requesting a 302-style Time Gate */
					$resource = new TimeGateResourceFrom302TimeNegotiation(
						$conf, $dbr, $article );
				}

			} else {
				$resource = new OriginalResourceDirectlyAccessed(
						$conf, $dbr, $article );
			}
		} else {
			// we are requesting a Memento directly (an oldID URI)
			$resource = new MementoResourceDirectlyAccessed(
				$conf, $dbr, $article );
		}

		return $resource;
	}

	/*
	 * fixTemplate
	 *
	 * This code ensures that the version of the Template that was in existence
	 * at the same time as the Memento gets loaded and displayed with the
	 * Memento.
	 *
	 * @param $title - Title object of the page
	 * @param $parser - Parsger object of the page
	 * @param $id - the revision id of the page
	 *
	 * @return array containing the text, finalTitle, and deps
	 */
	public function fixTemplate( $title, $parser, &$id ) {

		$request = $parser->getUser()->getRequest();

		if ( $request->getHeader('ACCEPT-DATETIME') ) {

			$requestDatetime = $request->getHeader('ACCEPT-DATETIME');

			$mwMementoTimestamp = $this->parseRequestDateTime(
				$requestDatetime );

			$firstRev = $title->getFirstRevision();

			if ( $firstRev->getTimestamp() < $mwMementoTimestamp ) {

				$pg_id = $title->getArticleID();

				$this->dbr->begin();

				$res = $this->dbr->select(
					'revision',
					array( 'rev_id' ),
					array(
						'rev_page' => $pg_id,
						'rev_timestamp <=' .
							$this->dbr->addQuotes( $mwMementoTimestamp )
						),
					__METHOD__,
					array( 'ORDER BY' => 'rev_id DESC', 'LIMIT' => '1' )
				);

				if( $res ) {
					$row = $this->dbr->fetchObject( $res );
					$id = $row->rev_id;
				}
			} else {
				// if we get something prior to the first memento, just
				// go with the first one
				$id = $firstRev->getId();
			}
		}
	}

	/**
	 * Constructor for MementoResource and its children
	 * 
	 * @param $conf - configuration object
	 * @param $dbr - database object
	 * @param $article - article object
	 *
	 */
	public function __construct( $conf, $dbr, $article ) {

		$this->conf = $conf;
		$this->dbr = $dbr;
		$this->article = $article;

		$waddress = str_replace( '/$1', '', $conf->get('ArticlePath') );

		$this->mwrelurl = $waddress;
	}


	/**
	 * alterHeaders
	 *
	 * This function is used to alter the headers of the outgoing response,
	 * and must be implemented by the MementoResource implementation.
	 * It is expected to be called from the ArticleViewHeader hook.
	 *
	 */
	abstract public function alterHeaders();

	/**
	 * alterEntity
	 *
	 * This function is used to alter the entity of the outgoing response,
	 * and must be implemented by the MementoResource implementation.
	 * It is expected to be callsed from the BeforePageDisplay hook.
	 *
	 */
	abstract public function alterEntity();

}
