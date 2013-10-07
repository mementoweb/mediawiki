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

class TimeMapPivotDescendingResource extends TimeMapResource {

	/**
	 * Render the page
	 * 
	 * TODO: There is too much duplication here with 
	 * TimeMapPivotAscendingResource; centralize this functionality into
	 * a method inside TimeMapResource and pass in an argument for which
	 * results method to use.
	 *
	 */
	public function render() {

		$server = $this->conf->get('Server');
		$pg_id = $this->title->getArticleID();
		$title = $this->title->getPrefixedURL();
		$response = $this->out->getRequest()->response();

		if ( $pg_id > 0 ) {

			$timestamp = $this->extractTimestampPivot( $this->urlparam );

			if (!$timestamp) {
				// we can't trust what came back, and we don't know the pivot
				// so the parameter array is empty below
				throw new MementoResourceException(
					'timemap-400-date', 'timemap',
					$this->out, $response, 400,
					array( '' ) );
			}

			$formattedTimestamp =
				$this->formatTimestampForDatabase( $timestamp );

			$results = $this->getDescendingTimeMapData(
				$pg_id, $this->conf->get('NumberOfMementos'),
				$formattedTimestamp
				);

			// this section is rather redundant when we throw 400 for
			// the timestamp above, but exists in case some how an invalid
			// timestamp is extracted
			if (!$results) {
				throw new MementoResourceException(
					'timemap-400-date', 'timemap',
					$this->out, $response, 400,
					array( $timestamp )
				);
			}

			$earliestItem = end($results);
			reset($results);

			$firstId = $this->title->getFirstRevision()->getId();

			echo "earliest is $earliestItem[rev_id]\n";
			echo "firstId is $firstId\n";

			# this counts revisions BETWEEN, non-inclusive
			$revCount = $this->title->countRevisionsBetween(
				$firstId, $earliestItem['rev_id'] );

			echo "revCount = $revCount\n";

			$revCount = $revCount + 2; # for first and last

			$timeMapPages = array();

			echo "revCount is $revCount\n";

			if ( $revCount > $this->conf->get('NumberOfMementos') ) {

				$pivotTimestamp = $this->formatTimestampForDatabase(
					$earliestItem['rev_timestamp'] );
	
				$this->generateDescendingTimeMapPaginationData(
					$pg_id, $pivotTimestamp, $timeMapPages, $title );

			}

			$pageURL = $this->title->getFullURL();

			echo $this->generateTimeMapText(
				$results, $this->urlparam, $this->mwbaseurl, $title, $pageURL,
				$timeMapPages );

			$response->header("Content-Type: text/plain", true);

			$this->out->disable();
		} else {
			$titleMessage = 'timemap';
			$textMessage = 'timemap-404-title';
			$waddress = str_replace(
				'$1', '', $this->conf->get('ArticlePath') );

			$title = str_replace(
				$server . $waddress, "",
				$this->title->getFullURL()
				);

			throw new MementoResourceException(
				$textMessage, $titleMessage,
				$this->out, $response, 404, array( $title )
			);
		}
	}

}
