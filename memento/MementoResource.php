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
	 * fetchMementoFromDatabase
	 *
	 * Make the actual database call.
	 *
	 * @param $sqlCondition - the conditional statement
	 * @param $sqlOrder - order of the data returned (e.g. ASC, DESC)
	 *
	 * returns $revision - associative array with id and timestamp keys
	 */
	public function fetchMementoFromDatabase( $dbr, $sqlCondition, $sqlOrder ) {

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

		if ( $givenTimestamp < $firstTimestamp ) {
			$chosenTimestamp = $firstTimestamp;
		} elseif ( $givenTimestamp > $lastTimestamp ) {
			$chosenTimestamp = $lastTimestamp;
		}

		return $chosenTimestamp;
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

		$entry = '<' . wfExpandUrl( $scriptUrl . '/' . $title ) .
			'>; rel="original latest-version", ';

		$entry .= '<' .
			wfExpandUrl(
				$scriptUrl . '/' . SpecialPage::getTitleFor( 'TimeMap' )
				) . '/' .
			wfExpandUrl( $scriptUrl . '/' . $title ) .
			'>; rel="timemap"; type="application/link-format"';

		return $entry;
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
	 * Constructor
	 * 
	 * @param $out
	 * @param $conf
	 * @param $dbr
	 */
	public function __construct( $out, $conf, $dbr ) {
		$this->out = $out;
		$this->conf = $conf;
		$this->dbr = $dbr;

		$waddress = str_replace( '/$1', '', $conf->get('ArticlePath') );

		$this->mwbaseurl = $this->conf->get('Server') . $waddress;
		$this->mwrelurl = $waddress;
	}


	abstract public function render();

}
