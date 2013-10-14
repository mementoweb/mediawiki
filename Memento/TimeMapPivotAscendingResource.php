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

class TimeMapPivotAscendingResource extends TimeMapResource {


	/**
	 * Render the page
	 *
	 */
	public function render() {

		$article = $this->article;
		$out = $article->getContext()->getOutput();
		$titleObj = $article->getTitle();

		$server = $this->conf->get('Server');
		$pg_id = $titleObj->getArticleID();
		$request = $out->getRequest();
		$response = $request->response();

		$urlparam = $request->getRequestURL();
		$timeMapURI = $request->getFullRequestURL();

		if ( $pg_id > 0 ) {

			$timestamp = $this->extractTimestampPivot( $urlparam );

			if (!$timestamp) {
				// we can't trust what came back, and we don't know the pivot
				// so the parameter array is empty below
				throw new MementoResourceException(
					'timemap-400-date', 'timemap',
					$out, $response, 400,
					array( '' ) );
			}

			$formattedTimestamp =
				$this->formatTimestampForDatabase( $timestamp );

			$results = $this->getAscendingTimeMapData(
				$pg_id, $this->conf->get('NumberOfMementos'),
				$formattedTimestamp
				);

			// this section is rather redundant when we throw 400 for
			// the timestamp above, but exists in case some how an invalid
			// timestamp is extracted
			if (!$results) {
				throw new MementoResourceException(
					'timemap-400-date', 'timemap',
					$out, $response, 400,
					array( $timestamp )
				);
			}

			$latestItem = $results[0];
			$earliestItem = end($results);
			reset($results);

			$firstId = $titleObj->getFirstRevision()->getId();
			$lastId = $titleObj->getLatestRevId();

			# this counts revisions BETWEEN, non-inclusive
			$revCount = $titleObj->countRevisionsBetween(
				$firstId, $earliestItem['rev_id'] );
			$revCount = $revCount + 2; # for first and last

			$timeMapPages = array();

			$title = $titleObj->getPrefixedURL();

			# if $revCount is higher, then we've gone over the limit
			if ( $revCount > $this->conf->get('NumberOfMementos') ) {

				$pivotTimestamp = $this->formatTimestampForDatabase(
					$earliestItem['rev_timestamp'] );
	
				$this->generateDescendingTimeMapPaginationData(
					$pg_id, $pivotTimestamp, $timeMapPages, $title );

			}

			# this counts revisions BETWEEN, non-inclusive
			$revCount = $titleObj->countRevisionsBetween(
				$latestItem['rev_id'], $lastId );
			$revCount = $revCount + 2; # for first and last

			# if $revCount is higher, then we've gone over the limit
			if ( $revCount > $this->conf->get('NumberOfMementos') ) {

				$pivotTimestamp = $this->formatTimestampForDatabase(
					$latestItem['rev_timestamp'] );

				$this->generateAscendingTimeMapPaginationData(
					$pg_id, $pivotTimestamp, $timeMapPages, $title );

			}

			echo $this->generateTimeMapText(
				$results, $timeMapURI, $title, $timeMapPages
				);

			$response->header("Content-Type: application/link-format", true);

			$out->disable();
		} else {
			$titleMessage = 'timemap';
			$textMessage = 'timemap-404-title';
			$title = $this->getFullNamespacePageTitle( $titleObj );

			throw new MementoResourceException(
				$textMessage, $titleMessage,
				$this->out, $response, 404, array( $title )
			);
		}
	}

	/**
	 * alterHeaders
	 *
	 * Special:TimeMap doesn't work like the other MementoResource classes.
	 */
	public function alterHeaders() {
		// do nothing to the headers
	}

	/**
	 * alterEntity
	 *
	 * Special:TimeMap doesn't work like the other MementoResource classes.
	 *
	 */
	public function alterEntity() {
		// do nothing to the body
	}

}
