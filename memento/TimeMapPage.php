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
abstract class TimeMapPage extends MementoResource {

	/**
	 * @var $urlparam - parameter part of the Special Page
	 */
	protected $urlparam;

	/**
	 * @var $title - Title Object created from calling Special Page
	 */
	protected $title;

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
	 * generateTimeMapText
	 *
	 * @param $data - array with entries containing the keys
	 *					rev_id and rev_timestamp
	 */
	public function generateTimeMapText(
		$data, $urlparam, $baseURL, $title) {

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

		for ($i = count($data) - 1; $i >= 0; $i--) {
		    $datum = $data[$i];
			$output .= '<' . $baseURL . "?title=$title";
			$output .= '&oldid=' . $datum['rev_id'] . '>';
			$output .= 'rel="memento";';
			$output .= 'datetime="' . $datum['rev_timestamp'] . '",' . "\n";
		}

		return $output;
	}

}

?>
