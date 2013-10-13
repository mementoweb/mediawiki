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
	 * @var object $out: OutputPage object for Memento Extension
	 */
	protected $out;

	/**
	 * @var object $conf: configuration object for Memento Extension
	 */
	protected $conf;

	/**
	 * @var object $dbr: DatabaseBase object for Memento Extension
	 */
	protected $dbr;

	/**
	 * @var string $mwbaseurl: Base URL for Mediawiki installation
	 */
	protected $mwbaseurl;

	/**
	 * @var string $mwrelurl: Base relative URL for Mediawiki installation
	 */
	protected $mwrelurl;

	/**
	 * @var $urlparam - parameter part of the Special Page
	 */
	protected $urlparam;

	/**
	 * @var $title - Title Object created from calling Special Page
	 */
	protected $title;

	/**
	 * @var $mementoOldID - timestamp of the Memento
	 */
	protected $mementoOldID;

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
	public function fetchMementoFromDatabase($dbr, $sqlCondition, $sqlOrder ) {

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
	 * getInfoForThisMemento
	 *
	 * Get information for the given oldID.
	 *
	 * @param $dbr - DatabaseBase object
	 * @param $oldid - the oldid for this page
	 *
	 * @return $revision - associative array with id and timestamp keys
	 */
	public function getInfoForThisMemento( $dbr, $oldid ) {

		$results = $dbr->select(
					'revision',
					array( 'rev_page', 'rev_timestamp' ),
					array( 'rev_id' => $oldid ),
					__METHOD__,
					array()
					);

		$row = $dbr->fetchObject( $results );

		$revision = array();

		if ( $row ) {
			$revision['id'] = $row->rev_page;
			$revision['timestamp'] = $row->rev_timestamp;
		}

		return $revision;

	}

	/**
	 * getFirstMemento
	 *
	 * Extract the first memento from the database.
	 *
	 * @param $dbr - DatabaseBase object
	 * @param $pageID - page identifier
	 * @param $pageTimestamp - timestamp used for finding the first memento
	 *
	 * @return $revision - associative array with id and timestamp keys
	 */
	public function getFirstMemento( $dbr, $pageID ) {

		$sqlCondition =
			array(
				'rev_page' => $pageID
				);
		$sqlOrder = 'rev_timestamp ASC';

		return $this->fetchMementoFromDatabase(
			$dbr, $sqlCondition, $sqlOrder );
	}

	/**
	 * getLastMemento
	 *
	 * Extract the last memento from the database.
	 *
	 * @param $dbr - DatabaseBase object
	 * @param $pageID - page identifier
	 * @param $pageTimestamp - timestamp used for finding the last memento
	 *
	 * @return $revision - associative array with id and timestamp keys
	 */
	public function getLastMemento( $dbr, $pageID ) {

		$sqlCondition =
			array(
				'rev_page' => $pageID
				);
		$sqlOrder = 'rev_timestamp DESC';

		return $this->fetchMementoFromDatabase(
			$dbr, $sqlCondition, $sqlOrder );
	}

	/**
	 * getCurrentMemento
	 *
	 * Extract the last memento from the database.
	 *
	 * @param $dbr - DatabaseBase object
	 * @param $pageID - page identifier
	 * @param $pageTimestamp - timestamp used for finding the last memento
	 *
	 * @return $revision - associative array with id and timestamp keys
	 */
	public function getCurrentMemento( $dbr, $pageID, $pageTimestamp ) {

		$sqlCondition =
			array(
				'rev_page' => $pageID,
				'rev_timestamp<=' . $dbr->addQuotes( $pageTimestamp )
				);
		$sqlOrder = 'rev_timestamp DESC';

		return $this->fetchMementoFromDatabase(
			$dbr, $sqlCondition, $sqlOrder );
	}

	/**
	 * getNextMemento
	 *
	 * Extract the last memento from the database.
	 *
	 * @param $dbr - DatabaseBase object
	 * @param $pageID - page identifier
	 * @param $pageTimestamp - timestamp used for finding the last memento
	 *
	 * @return $revision - associative array with id and timestamp keys
	 */
	public function getNextMemento( $dbr, $pageID, $pageTimestamp ) {

		$sqlCondition =
			array(
				'rev_page' => $pageID,
				'rev_timestamp>' . $dbr->addQuotes( $pageTimestamp )
				);
		$sqlOrder = 'rev_timestamp ASC';

		return $this->fetchMementoFromDatabase(
			$dbr, $sqlCondition, $sqlOrder );
	}

	/**
	 * getPrevMemento
	 *
	 * Extract the last memento from the database.
	 *
	 * @param $dbr - DatabaseBase object
	 * @param $pageID - page identifier
	 * @param $pageTimestamp - timestamp used for finding the last memento
	 *
	 * @return $revision - associative array with id and timestamp keys
	 */
	public function getPrevMemento( $dbr, $pageID, $pageTimestamp ) {

		$sqlCondition =
			array(
				'rev_page' => $pageID,
				'rev_timestamp<' . $dbr->addQuotes( $pageTimestamp )
				);
		$sqlOrder = 'rev_timestamp DESC';

		return $this->fetchMementoFromDatabase(
			$dbr, $sqlCondition, $sqlOrder );
	}

	/**
	 * getFullURIForID
	 *
	 * @param $mwBaseURL - Base URL of Mediawiki installation 
	 * 			(e.g. http://e.com/index.php)
	 * @param $id - ID of page
	 * @param $title - article title
	 *
	 * @return $fullURI - full URI referring to article and revision
	 */
	public function getFullURIForID( $scriptPath, $id, $title ) {

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
	 * @param $scriptUrl - the base URI used for Mediawiki
	 * @param $title - the title string of the given page
	 * @param $oldid - the oldid of the given page
	 * @param $timestamp - the timestamp of this Memento
	 * @param $relation - the relation type of this Memento
	 *
	 * @return $entry - full Memento Link header entry
	 */
	public function constructMementoLinkHeaderEntry(
		$scriptUrl, $title, $id, $timestamp, $relation ) {

		$url = $this->getFullURIForID( $scriptUrl, $id, $title );

		$entry = '<' . $url . '>; rel="' . $relation . '"; datetime="' .
			$timestamp . '"';

		return $entry;

	}

	/**
	 * constructTimeMapLinkHeaderWithBounds
	 *
	 * This creates the entry for timemap in the Link Header.
	 *
	 * @param $scriptUrl - the base URL used for Mediawiki
	 * @param $title - the title string of the given page
	 * @param $from - the from timestamp for the TimeMap
	 * @param $until - the until timestamp for the TimeMap
	 *
	 * @return $entry - full Memento TimeMap relation with from and until
	 */
	public function constructTimeMapLinkHeaderWithBounds(
		$scriptUrl, $title, $from, $until ) {

		$entry = $this->constructTimeMapLinkHeader( $scriptUrl, $title );

		$entry .= "; from=\"$from\"; until=\"$until\"";

		return $entry;
	}

	/**
	 * constructTimeMapLinkHeader
	 *
	 * This creates the entry for timemap in the Link Header.
	 *
	 * @param $scriptUrl - the base URL used for Mediawiki
	 * @param $title - the title string of the given page
	 *
	 * @return $entry - Memento TimeMap relation
	 */
	public function constructTimeMapLinkHeader( $scriptUrl, $title ) {

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
	 * @param $scriptUrl - the base URL used for Mediawiki
	 * @param $title - the title string of the given page
	 *
	 * @return $safeURI - the safely formed URI
	 */
	public function getSafelyFormedURI( $scriptUrl, $title ) {

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
	 * convertRevisionData
	 *
	 * The database functions return ID and Timestamp, but so many of the
	 * functions need URI and Timestamp, so this function converts them.
	 *
	 * @param $revision - associative array consisting of id and timestamp keys
	 * @param $title - the title of the article
	 *
	 * @return $convertedRev - associative array consisting of uri and dt keys
	 */
	public function convertRevisionData( $scriptPath, $revision, $title ) {

		$convertedRev = array();

		if ($revision) {
			$convertedRev = array(
				'uri' => $this->getFullURIForID(
					$scriptPath, $revision['id'], $title ),
				'dt' => $revision['timestamp']
			);
		}

		return $convertedRev;
	}

	// TODO: This function is not useful unless the get*Memento functions
	// 		return arrays with keys of uri and dt, meaning they have to
	//		know about the web address and title of the page, violating
	//		the "do one thing and do it well" concept
	/**
	 * Constructs and returns a string with urls and rel types as defined 
	 * in the memento RFC.
	 *
	 * The constructed string is compatible with the link header format.
	 * Checks and concats rel types, if the url passed in the different 
	 * parameters are same.
	 *
	 * @param $first: associative array, not optional.
	 *	  Contains url and datetime info for the first memento of a resource.
	 *	  $first['uri'] is the url of the first memento.
	 *	  $first['dt'] is the datetime of the first memento.
	 * @param $last: associative array, not optional.
	 *	  Contains url and datetime info for the last memento of a resource.
	 *	  $last['uri'] is the url of the last memento.
	 *	  $last['dt'] is the datetime of the last memento.
	 * @param $mem: associative array, optional.
	 *	  Contains url and datetime info for the memento of a resource.
	 *	  $mem['uri'] is the url of the memento.
	 *	  $mem['dt'] is the datetime of the memento.
	 * @param $next: associative array, optional.
	 *	  Contains url and datetime info for the next memento of a resource.
	 *	  $next['uri'] is the url of the next memento.
	 *	  $next['dt'] is the datetime of the next memento.
	 * @param $prev: associative array, optional.
	 *	  Contains url and datetime info for the prev memento of a resource.
	 *	  $prev['uri'] is the url of the prev memento.
	 *	  $prev['dt'] is the datetime of the prev memento.
	 * @return String, the constructed link header.
	 */
	public function constructLinkHeader(
			$first, $last, $mem = '', $next = '', $prev = ''
		) {
		$dt = $first['dt'];
		$uri = $first['uri'];
		$mflag = false;
		$rel = "first";

		if ( isset( $last['uri'] ) && $last['uri'] == $uri ) {
			$rel .= " last";
			unset( $last );
		}
		if ( isset( $prev['uri'] ) && $prev['uri'] == $uri ) {
			$rel .= " prev predecessor-version";
			unset( $prev );
		}
		elseif ( isset( $mem['uri'] ) && $mem['uri'] == $uri ) {
			$rel .= " memento";
			$mflag = true;
			unset( $mem );
		}

		if ( !$mflag )
			$rel .= " memento";
		$link = "<$uri>;rel=\"$rel\";datetime=\"$dt\", ";

		if ( $last ) {
			$dt = $last['dt'];
			$uri = $last['uri'];
			$rel = "last";
			$mflag = false;

			if ( isset( $mem['uri'] ) && $mem['uri'] == $uri ) {
				$rel .= " memento";
				$mflag = true;
				unset( $mem );
			}
			elseif ( isset( $next['uri'] ) && $next['uri'] == $uri ) {
				$rel .= " next successor-version";
				unset( $next );
			}
			if ( !$mflag )
				$rel .= " memento";
			$link .= "<$uri>;rel=\"$rel\";datetime=\"$dt\", ";
		}

		if ( isset( $prev['uri'] ) )
			$link .= "<" . $prev['uri'] . ">;" .
			"rel=\"prev predecessor-version memento\";" .
			"datetime=\"" . $prev['dt'] . "\", ";

		if ( isset( $next['uri'] ) )
			$link .= "<" . $next['uri'] . ">;" .
			"rel=\"next successor-version memento\";" .
			"datetime=\"" . $next['dt'] . "\", ";

		if ( isset( $mem['uri'] ) )
			$link .= "<" . $mem['uri'] . ">;" .
			"rel=\"memento\";" .
			"datetime=\"" . $mem['dt'] . "\", ";

		return $link;
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
	 * TODO: this function doesn't handle mementos that exist prior to the 
	 *			first memento
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

		$this->mwbaseurl = $this->conf->get('Server') . $waddress;
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
