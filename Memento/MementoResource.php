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
	 */
	public function getStatusCode() {
		return $this->statusCode;
	}

	/**
	 * getter for response
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * getter for outputPage
	 */
	public function getOutputPage() {
		return $this->outputPage;
	}

	/**
	 * getter for textMessage
	 */
	public function getTextMessage() {
		return $this->textMessage;
	}

	/**
	 * getter for titleMessage
	 */
	public function getTitleMessage() {
		return $this->titleMessage;
	}

	/**
	 * getter for params
	 */
	public function getParams() {
		return $this->params;
	}

}

/**
 * This abstract class is the parent of all MementoResource types.
 *
 * As such, it contains the methods used by all of the Memento Pages.
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
	 * fetchMementoFromDatabase
	 *
	 * Make the actual database call.
	 *
	 * @param $sqlCondition - the conditional statement
	 * @param $sqlOrder - order of the data returned (e.g. ASC, DESC)
	 *
	 * returns $revision - associative array with id and timestamp keys
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
	 * getInforForThisMemento
	 *
	 * Get information for the given oldID.
	 *
	 * @param $dbr - DatabaseBase object
	 * @param $oldid - the oldid for this page
	 *
	 * returns $revision - associative array with id and timestamp keys
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
	 * returns $revision - associative array with id and timestamp keys
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
	 * returns $revision - associative array with id and timestamp keys
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
	 * returns $revision - associative array with id and timestamp keys
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
	 * returns $revision - associative array with id and timestamp keys
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
	 * returns $revision - associative array with id and timestamp keys
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
	 * return $fullURI - full URI referring to article and revision
	 */
	public function getFullURIForID( $scriptPath, $id, $title ) {

		return wfAppendQuery(
			wfExpandUrl( $scriptPath ),
			array( 'title' => $title, 'oldid' => $id )
			);

	}

	/**
	 * generateSpecialURL
	 *
	 * @param $urlparam - url from the SpecialPage call
	 * @param $middletext - Special:SpecialPage part of URL
	 * @param $baseURL - the base URL for the Mediawiki installation
	 */
	public function generateSpecialURL($urlparam, $middletext, $baseURL) {

		if ( $baseURL[strlen($baseURL) - 1] == '/' ) {
			$baseURL = substr($baseURL, 0, strlen($baseURL) - 1);
		}

		$specialPageText = SpecialPage::getTitleFor($middletext);

		return implode('/', array($baseURL, $specialPageText, $urlparam));
	}

	/**
	 * parseRequestDateTime
	 *
	 * @param $requestDateTime
	 *
	 *
	 * returns $dt - datetime in mediawiki database format
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
	 *
	 * @param $firstTimestamp - the first timestamp for which we have a memento
	 *				formatted in the TS_MW format
	 * @param $lastTimestamp - the last timestamp for which we have a memento
	 * @param $givenTimestamp - the timestamp given by the request header
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
	 * constructFirstMementoLinkHeader
	 *
	 * This creates the entry for the first memento in the Link Header.
	 *
	 * @param $scriptUrl
	 * @param $title
	 * @param $oldid
	 * @param $timestamp
	 * @param $relation
	 */
	public function constructMementoLinkHeaderEntry(
		$scriptUrl, $title, $id, $timestamp, $relation ) {

		$url = $this->getFullURIForID( $scriptUrl, $id, $title );

		$entry = '<' . $url . '>; rel="' . $relation . '"; datetime="' .
			$timestamp . '"';

		return $entry;

	}


	/**
	 * constructTimeMapLinkHeader
	 *
	 * This creates the entry for timemap in the Link Header.
	 *
	 * @param $scriptUrl
	 * @param $title
	 */
	public function constructTimeMapLinkHeader( $scriptUrl, $title ) {
		$entry = '<' .
			wfExpandUrl(
				$scriptUrl . '/' . SpecialPage::getTitleFor( 'TimeMap' )
				) . '/' .
			wfExpandUrl( $scriptUrl . '/' . $title ) .
			'>; rel="timemap"; type="application/link-format"';

		return $entry;
	}

	/**
	 * constructTimeGateLinkHeader
	 *
	 * This creates the entry for timegate in the Link Header.
	 *
	 * @param $scriptUrl
	 * @param $title
	 */
	public function constructTimeGateLinkHeader( $scriptUrl, $title ) {

		$title = rawurlencode($title);

		$entry = '<' .
			wfExpandUrl(
				$scriptUrl . '/' . SpecialPage::getTitleFor( 'TimeGate' )
				) . '/' .
			wfExpandUrl( $scriptUrl . '/' . $title ) .
			'>; rel="timegate"';

		return $entry;
	}

	/**
	 * constructOriginalLatestVersionHeader
	 *
	 * This creates the entry for timegate in the Link Header.
	 *
	 * @param $scriptUrl
	 * @param $title
	 */
	public function constructOriginalLatestVersionLinkHeader(
		$scriptUrl, $title ) {

		$entry = '<' . wfExpandUrl( $scriptUrl . '/' . $title ) .
			'>; rel="original latest-version"';

		return $entry;
	}

	/**
	 * constructAdditionalLinkHeader
	 *
	 * This creates the entries for timemap and "original latest-version"
	 * relations, for use in the Link Header.
	 *
	 * @param $scriptUrl
	 * @param $title
	 */
	public function constructAdditionalLinkHeader( $scriptUrl, $title ) {

		$title = rawurlencode($title);

		$originalURI = wfAppendQuery(
				wfExpandUrl( $scriptUrl . '/' . $title ),
				array()
				);

		$entry = '<' . $originalURI .  '>; rel="original latest-version", ';

		$entry .= '<' .
			wfExpandUrl(
				$scriptUrl . '/' . SpecialPage::getTitleFor( 'TimeMap' )
				) . '/' .
			wfExpandUrl( $scriptUrl . '/' . $title ) .
			'>; rel="timemap"; type="application/link-format"';

		return $entry;
	}

	/**
	 * convertRevisionData
	 *
	 * @param $revision - associative array consisting of id and timestamp keys
	 * @param $title - the title of the article
	 *
	 * returns $convertedRev - associative array consisting of uri and dt keys
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
	 * renderError
	 *
	 * Render error page.  This is only used for 40* and 50* errors.
	 *
	 * @param $error - MementoResourceException object
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
	 * @param $out - OutputPage object, passed to constructor
	 * @param $conf - MementoConfig object, passed to constructor
	 * @param $dbr - DatabaseBase object, passed to constructor
	 * @param $oldID - string indicating revision ID
	 *		used in decision
	 *
	 */
	public static function mementoPageResourceFactory(
		$out, $conf, $dbr, $oldID, $title, $article ) {

		$page = null;
		$request = $out->getRequest();

		if ( $oldID == 0 ) {

			if ( $conf->get('Negotiation') ) {

				if ( $request->getHeader('ACCEPT-DATETIME') ) {
					$page = new MementoResourceFromTimeNegotiation(
						$out, $conf, $dbr, $title, null, $article );
				} else {
					$page = new OriginalResourceWithTimeNegotiation(
						$out, $conf, $dbr, $title, null, $article );
				}

			} else {
				$page = new OriginalResourceWithHeaderModificationsOnly(
					$out, $conf, $dbr, $title, null, $article );
			}
		} else {
			$page = new MementoResourceWithHeaderModificationsOnly(
				$out, $conf, $dbr, $title, null, $article );
		}

		return $page;
	}

	/**
	 * Constructor
	 * 
	 * @param $out
	 * @param $conf
	 * @param $dbr
	 * @param $title
	 * @param $urlparam
	 */
	public function __construct(
		$out, $conf, $dbr, $title, $urlparam, $article ) {

		$this->out = $out;
		$this->conf = $conf;
		$this->dbr = $dbr;
		$this->title = $title;
		$this->urlparam = $urlparam;
		$this->article = $article;

		$waddress = str_replace( '/$1', '', $conf->get('ArticlePath') );

		$this->mwbaseurl = $this->conf->get('Server') . $waddress;
		$this->mwrelurl = $waddress;
	}


	abstract public function render();

}
