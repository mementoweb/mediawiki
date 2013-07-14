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

class TimeMapPage extends MementoResource {

	/**
	 * @var $urlparam - parameter part of the Special Page
	 */
	private $urlparam;

	/**
	 * @var $title - Title Object created from calling Special Page
	 */
	private $title;

	/**
	 * Constructor
	 * 
	 * @param $out
	 * @param $conf
	 * @param $dbr
	 * @param $urlparam
	 * @param $title
	 *
	 */
	public function __construct( $out, $conf, $dbr, $urlparam, $title ) {
		$this->urlparam = $urlparam;
		$this->title = $title;

		parent::__construct( $out, $conf, $dbr );
	}

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
	 * generateTimeMapText
	 *
	 * @param $data - array with entries containing the keys
	 *					rev_id and rev_timestamp
	 */
	public function generateTimeMapText(
		$data, $urlparam, $baseURL, $title) {

		/*
			1. generate TimeGate URL 'timegate' X
			2. generate TimeMap URL 'timemap' X
			3. generate from and until parts of timemap entry X
			4. generate Original Page URL 'original latest-version'
			5. generate memento URLs with datetimes
		*/

		$timegateURL = $this->generateSpecialURL(
			$urlparam, "Special:TimeGate", $baseURL);

		$timemapURL = $this->generateSpecialURL(
			$urlparam, "Special:TimeMap", $baseURL);

		$from = $data[count($data) - 1]['rev_timestamp'];
		$until = $data[0]['rev_timestamp'];

		$output = "<$timegateURL>;rel=\"timegate\",\n";
		$output .= "<$timemapURL>;rel=\"self\";";
		$output .= "from=\"$from\";until=\"$until\",\n";
		$output .= "<$urlparam>;rel=\"original latest-version\",\n";

		#foreach ($data as $datum) {
		for ($i = count($data) - 1; $i >= 0; $i--) {
		    $datum = $data[$i];
			$output .= '<' . $baseURL . "?title=$title";
			$output .= '&oldid=' . $datum['rev_id'] . '>';
			$output .= 'rel="memento";';
			$output .= 'datetime="' . $datum['rev_timestamp'] . '",' . "\n";
		}

		return $output;

	}

	/**
	 * Render the page
	 */
	public function render() {

		$server = $this->conf->get('Server');
		$pg_id = $this->title->getArticleID();
		$title = $this->title->getPrefixedURL();

		$results = $this->getFullTimeMapData(
			$pg_id, $this->conf->get('NumberOfMementos')
			);

		echo $this->generateTimeMapText(
			$results, $this->urlparam, $this->mwbaseurl, $title
			);
		$response = $this->out->getRequest()->response();

		$response->header("Content-Type: text/plain", true);

		$this->out->disable();
	}

}

?>
