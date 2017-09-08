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
 * Ensure that this file is only executed in the right context.
 *

 */

/**
 * This class implements Full (starter) Time Map rendering for Time Maps
 * of the form:
 * http://example.com/index.php/Special:TimeMap/Page
 */
class TimeMapFullResource extends TimeMapResource {

	/**
	 * getPivotTimeMapData
	 *
	 * Concrete implementation of a method that acquires no pivoted
	 * TimeMap data, because Full Time Maps aren't generated based on
	 * a pivot date.
	 *
	 * @param int $pageID
	 * @param string $formattedTimestamp
	 *
	 * @return null
	 */
	public function getPivotTimeMapData( $pageID, $formattedTimestamp ) {
		return null;
	}

	/**
	 * Render the page
	 */
	public function alterEntity() {
		$this->renderFullTimeMap();
	}

	/**
	 * alterHeaders
	 *
	 * No headers to alter for Time Maps.
	 */
	public function alterHeaders() {
		// do nothing to the headers
	}

}
