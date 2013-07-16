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

class TimeMapPivotDescendingPage extends TimeMapPage {

	/**
	 * getFullTimeMapData
	 *
	 * Extract the full time map data from the database.
	 *
	 * @param $pg_id - identifier of the requested page
	 * @param $limit - the greatest number of results
	 *
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
	 * Render the page
	 */
	public function render() {

		$server = $this->conf->get('Server');
		$pg_id = $this->title->getArticleID();
		$title = $this->title->getPrefixedURL();
		$response = $this->out->getRequest()->response();

		if ( $pg_id > 0 ) {

			$timestamp = $this->extractTimestampPivot( $this->urlparam );

			$formattedTimestamp =
				$this->formatTimestampForDatabase( $timestamp );

			$results = $this->getDescendingTimeMapData(
				$pg_id, $this->conf->get('NumberOfMementos'),
				$formattedTimestamp
				);
	
			$pageURL = $this->extractPageURL($this->urlparam);
	
			echo $this->generateTimeMapText(
				$results, $this->urlparam, $this->mwbaseurl, $title, $pageURL
				);
	
			$response->header("Content-Type: text/plain", true);
	
			$this->out->disable();
		} else {
			$titleMessage = 'timemap';
			$textMessage = 'timemap-404-title';
			$waddress = str_replace( 
				'$1', '', $this->conf->get('ArticlePath') );

			$title = str_replace(
				$server . $waddress, "",
				$this->extractPageURL( $this->urlparam )
				);

			throw new MementoResourceException(
				$textMessage, $titleMessage, 
				$this->out, $response, 404, array( $title )
			);
		}
	}

}
?>
