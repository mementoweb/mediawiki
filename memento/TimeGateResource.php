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

class TimeGateResource extends SpecialPageResource {

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
			$revision['timestamp'] = wfTimestamp( TS_RFC2822, $row->rev_timestamp );
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
	 * Render the page
	 */
	public function render() {

		$first = array();
		$last = array();
		$memento = array();
		$next = array();
		$prev = array();

		$response = $this->out->getRequest()->response();
		$requestDatetime =
			$this->out->getRequest()->getHeader( 'ACCEPT-DATETIME' );

		$pageID = $this->title->getArticleID();
		$title = $this->title->getPartialURL();
		$response->header( "Vary: negotiate,accept-datetime", true );

		if ( !$this->title->exists() ) {
			throw new MementoResourceException(
				'timegate-404-title', 'timegate',
				$this->out, $response, 404, array( $title )
			);
		}

		$mwMementoTimestamp = $this->parseRequestDateTime( $requestDatetime );

		$first = $this->convertRevisionData( $this->mwrelurl,
			$this->getFirstMemento( $this->dbr, $pageID ),
			$title );

		$last = $this->convertRevisionData( $this->mwrelurl,
			$this->getLastMemento( $this->dbr, $pageID ),
			$title );

		if ( $mwMementoTimestamp ) {
			$mwMementoTimestamp = $this->chooseBestTimestamp(
				$first['dt'], $last['dt'], $mwMementoTimestamp );

			$memento = $this->convertRevisionData( $this->mwrelurl,
				$this->getCurrentMemento(
					$this->dbr, $pageID, $mwMementoTimestamp ),
				$title );

			$next = $this->convertRevisionData( $this->mwrelurl,
				$this->getNextMemento(
					$this->dbr, $pageID, $mwMementoTimestamp ),
				$title );

			$prev = $this->convertRevisionData( $this->mwrelurl,
				$this->getPrevMemento(
					$this->dbr, $pageID, $mwMementoTimestamp ),
				$title );
		}

		$linkEntries = $this->constructLinkHeader(
			$first, $last, $memento, $next, $prev );

		$linkEntries .= $this->constructAdditionalLinkHeader(
			$this->mwrelurl, $title );


		$response->header( "Link: $linkEntries", true );
		$response->header( "X-RequestedTimestamp:  $mwMementoTimestamp", true );

		if ( !$mwMementoTimestamp ) {
			throw new MementoResourceException(
				'timegate-400-date', 'timegate',
				$this->out, $response, 400,
				array( $requestDatetime, $first['uri'], $last['uri'] )
			);
		}

		$response->header( "HTTP", true, 302 );

		$mementoLocation = $memento['uri'];
		$response->header( "Location: $mementoLocation", true );

		// no output for a 302 response
		$this->out->disable();

	}

}
