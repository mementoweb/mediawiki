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
 * This class provides the base functions for all Memento TimeMap types
 */
abstract class TimeMapResource extends MementoResource {

	/**
	 * getDescendingTimeMapData
	 *
	 * Extract the full time map data from the database.
	 *
	 * @param $pg_id - identifier of the requested page
	 * @param $limit - the greatest number of results
	 * @param $timestamp - the timestamp to query for
	 *
	 * @return $data - array with keys 'rev_id' and 'rev_timestamp' containing
	 *		the revision ID and the revision timestamp respectively
	 */
	public function getDescendingTimeMapData($pg_id, $limit, $timestamp) {

		$data = array();

		$results = $this->dbr->select(
			'revision',
			array( 'rev_id', 'rev_timestamp'),
			array(
				'rev_page' => $pg_id,
				'rev_timestamp<' . $this->dbr->addQuotes( $timestamp )
				),
			__METHOD__,
			array(
				'ORDER BY' => 'rev_timestamp DESC',
				'LIMIT' => $limit
				)
			);

		while($result = $results->fetchRow()) {
			$datum = array();
			$datum['rev_id'] = $result['rev_id'];
			$datum['rev_timestamp'] = wfTimestamp(
				TS_RFC2822, $result['rev_timestamp']
				);
			$data[] = $datum;
		}

		return $data;
	}

	/**
	 * getAscendingTimeMapData
	 *
	 * Extract the full time map data from the database.
	 *
	 * @param $pg_id - identifier of the requested page
	 * @param $limit - the greatest number of results
	 *
	 * @return $data - array with keys 'rev_id' and 'rev_timestamp' containing
	 *		the revision ID and the revision timestamp respectively
	 */
	public function getAscendingTimeMapData($pg_id, $limit, $timestamp) {

		$data = array();

		$results = $this->dbr->select(
			'revision',
			array( 'rev_id', 'rev_timestamp'),
			array(
				'rev_page' => $pg_id,
				'rev_timestamp>' . $this->dbr->addQuotes( $timestamp )
				),
			__METHOD__,
			array(
				'ORDER BY' => 'rev_timestamp DESC',
				'LIMIT' => $limit
				)
			);

		while($result = $results->fetchRow()) {
			$datum = array();
			$datum['rev_id'] = $result['rev_id'];
			$datum['rev_timestamp'] = wfTimestamp(
				TS_RFC2822, $result['rev_timestamp']
				);
			$data[] = $datum;
		}

		return $data;
	}


	/**
	 * extractTimestampPivot
	 *
	 * @param $urlparam - the parameter passed to execute() in this SpecialPage
	 *
	 * @returns timestamp, if found; null otherwise
	 */
	public function extractTimestampPivot( $urlparam ) {
		$pivot = null;

		$pattern = "/^([0-9]{14})\/.*/";

		preg_match($pattern, $urlparam, $matches);

		if ( count($matches) == 2 ) {
			$pivot = $matches[1];
		} else {
			$pivot = null;
		}

		return $pivot;
	}

	/**
	 * formatTimestamp
	 *
	 * Wrapper for wfTimestamp that catches exceptions so the caller can issue 
	 * its own error statements instead.
	 *
	 * @see http://www.mediawiki.org/wiki/Manual:WfTimestamp
	 *
	 * @param $timestamp
	 *
	 * @returns formatted timestamp; null if error
	 */
	public function formatTimestampForDatabase( $timestamp ) {

		$formattedTimestamp = null;

		try {
			$formattedTimestamp = wfTimestamp( TS_MW, $timestamp );

			if ( $formattedTimestamp === false ) {
				// the timestamp is unrecognized, but not incorrectly formatted?
				$formattedTimestamp = null;
			}

		} catch ( MWException $e ) {
			// it all went wrong, we passed in bad data
			$formattedTimestamp = null;
		}

		return $formattedTimestamp;
	}

	/**
	 * generateTimeMapText
	 *
	 * Generates Time Map text as per examples in Memento TimeMap RFC
	 * @see http://www.mementoweb.org/guide/rfc/ID/
	 *
	 * @param $data - array with entries containing the keys
	 *					rev_id and rev_timestamp
	 * @param $urlparam - unused (delete)
	 * @param $baseURL - the base URI for the site
	 * @param $title - the page name that the TimeMap is for
	 * @param $pageURL - unused (delete)
	 * @param $pagedTimeMapEntries - array of arrays, each entry containing
	 *			the keys 'uri', 'from', and 'until' referring to the URI of
	 *			the TimeMap and its from and until dates
	 *
	 * @returns formatted timemap as a string
	 */
	public function generateTimeMapText(
		$data, $urlparam, $baseURL, $title, $pageURL,
		$pagedTimeMapEntries = array() ) {

		$outputArray = array();

		$timegateEntry = $this->constructTimeGateLinkHeader(
			$this->mwrelurl, $title );

		$from = $data[count($data) - 1]['rev_timestamp'];
		$until = $data[0]['rev_timestamp'];

		$timemapEntry = $this->constructTimeMapLinkHeaderWithBounds(
			$this->mwrelurl, $title, $from, $until );

		$timemapEntry = str_replace( 'rel="timemap";', 'rel="self";', $timemapEntry );

		$originalLatestVersionEntry =
			$this->constructOriginalLatestVersionLinkHeader(
				$this->mwrelurl, $title );

		array_push( $outputArray, $originalLatestVersionEntry );

		array_push( $outputArray, $timemapEntry );

		foreach ( $pagedTimeMapEntries as &$pagedTimeMap ) {
			$pagedTimemapEntry = '<' . $pagedTimeMap['uri'] .
				'>; rel="timemap"; from="' . $pagedTimeMap['from'] . '"; ' .
				'until="' . $pagedTimeMap['until'] . '",';
				
			array_push( $outputArray, $pagedTimemapEntry );	
		}

		array_push( $outputArray, $timegateEntry );

		$baseURL = rtrim($baseURL, "/");

		for ($i = count($data) - 1; $i >= 0; $i--) {
			$output = "";
			$datum = $data[$i];

			$output = $this->constructMementoLinkHeaderEntry(
				$this->mwrelurl, $title, $datum['rev_id'],
				$datum['rev_timestamp'], "memento" );

			array_push($outputArray, $output);
		}

		// the original implementation of TimeMap for Mediawiki used ,<SP><LF>
		// to separate the entries and added a \n at the end
		$timemap = implode(",\n", $outputArray);

		return $timemap;
	}

}
