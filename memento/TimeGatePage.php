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

class TimeGatePage extends MementoResource {

	/**
	 * Render the page
	 */
	public function render() {

		$response = $this->out->getRequest()->response();
		$requestDatetime =
			$this->out->getRequest()->getHeader( 'ACCEPT-DATETIME' );

		$response->header( "HTTP", true, 302 );
		$response->header( "Vary: negotiate,accept-datetime", true );

		# TODO Link header should contain original and timemap entries
		$response->header( "Link: something", true );

		# TODO Location header should contain location of Memento based on value from ACCEPT-DATETIME
		$response->header( "Location: something", true );

		// no output for a 302 response
		$this->out->disable();

	}

}
