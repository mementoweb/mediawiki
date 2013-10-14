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

class TimeMapFullResource extends TimeMapResource {

	/**
	 * getFullTimeMapData
	 *
	 * Extract the full time map data from the database.
	 *
	 * @param $pg_id - identifier of the requested page
	 * @param $limit - the greatest number of results
	 *
	 */
	public function getFullTimeMapData($pg_id, $limit) {

		$data = array();

		$results = $this->dbr->select(
			'revision',
			array( 'rev_id', 'rev_timestamp'),
			array( 'rev_page' => $pg_id ),
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
	 * Render the page
	 */
	public function render() {

		$article = $this->article;
		$out = $article->getContext()->getOutput();
		$titleObj = $article->getTitle();

		$server = $this->conf->get('Server');
		$pg_id = $article->getTitle()->getArticleID();
		$request = $out->getRequest();
		$response = $request->response();

		$timeMapURI = $request->getFullRequestURL();

		if ( $pg_id > 0 ) {

			$results = $this->getFullTimeMapData(
				$pg_id, $this->conf->get('NumberOfMementos')
				);

			# get the first revision ID
			$firstId = $titleObj->getFirstRevision()->getId();
			
			# get the last revision ID
			$lastId = $titleObj->getLatestRevID();

			# calculate the difference
			# this counts the revisions BETWEEN, non-inclusive
			$revCount = $titleObj->countRevisionsBetween($firstId, $lastId);
			$revCount = $revCount + 2; # for first and last

			# if it is greater than limit then get the revision ID prior to the
			#	lowest one returned by getFullTimeMapData

			# paginate if we have more than NumberOfMementos Mementos
			$timeMapPages = array();

			$title = $titleObj->getPrefixedURL();

			if ( $revCount > $this->conf->get('NumberOfMementos') ) {
				$earliestItem = end($results);
				reset($results);

				$pivotTimestamp =
					$this->formatTimestampForDatabase(
						$earliestItem['rev_timestamp'] );

				# this function operates on $timeMapPages in place
				$this->generateDescendingTimeMapPaginationData(
					$pg_id, $pivotTimestamp, $timeMapPages, $title );

			}

			# use that revision ID + limit revisions to calculate the from and
			# 	until for the next timemap

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
				$out, $response, 404, array( $title )
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
