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
	 *
	 * @returns formatted timemap as a string
	 */
	public function generateTimeMapText(
		$data, $urlparam, $baseURL, $title, $pageURL ) {

		$outputArray = array();

		$timegateURL = $this->generateSpecialURL(
			$title, "TimeGate", $baseURL);

		$selfURL = $this->generateSpecialURL(
				$urlparam, "TimeMap", $baseURL);

		$from = $data[count($data) - 1]['rev_timestamp'];
		$until = $data[0]['rev_timestamp'];

		array_push($outputArray, "<$pageURL>;rel=\"original latest-version\"");

		array_push($outputArray,
			"<$selfURL>;rel=\"self\";from=\"$from\";until=\"$until\"");

		array_push($outputArray, "<$timegateURL>;rel=\"timegate\"");

		$baseURL = rtrim($baseURL, "/");

		for ($i = count($data) - 1; $i >= 0; $i--) {
			$output = "";
			$datum = $data[$i];
			$output .= '<' . $baseURL . "?title=$title";
			$output .= '&oldid=' . $datum['rev_id'] . '>;';
			$output .= 'rel="memento";';
			$output .= 'datetime="' . $datum['rev_timestamp'] . '"';
			array_push($outputArray, $output);
		}

		// the original implementation of TimeMap for Mediawiki used ,<SP><LF>
		// to separate the entries and added a \n at the end
		$timemap = implode(",\n", $outputArray);

		return $timemap;
	}

}
