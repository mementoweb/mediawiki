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

class MementoWithHeaderModificationsResource extends OriginalResource {

	/**
	 * Render the page
	 */
	public function render() {

		/*
		$title = $this->title->getPartialURL();

		$first = $this->convertRevisionData( $this->mwrelurl,
			$this->getFirstMemento( $this->dbr, $pageID ),
			$title );

		$last = $this->convertRevisionData( $this->mwrelurl,
			$this->getLastMemento( $this->dbr, $pageID ),
			$title );

		/* resulting header needs:
		 * 		Link:
		 *			first memento
		 *			last memento
		 *			next successor-version memento
		 *			original latest-version
		 *			timemap
		 *		Memento-Datetime
		 *		
		 */
		/*
		$requestDatetime =
			$this->out->getRequest()->getHeader( 'ACCEPT-DATETIME' );

		$mwMementoTimestamp = $this->parseRequestDateTime( $requestDatetime );

		$memento = $this->convertRevisionData( $this->mwrelurl,
			$this->getCurrentMemento(
				$this->dbr, $pageID, $mwMementoTimestamp ),
			$title );

		$next = $this->convertRevisionData( $this->mwrelurl,
			$this->getNextMemento(
				$this->dbr, $pageID, $mwMementoTimestamp ),
			$title );

		$prev = $this->convertRevisionData( $this->mwrelurl,
			$this->getPrevMemento(
				$this->dbr, $pageID, $mwMementoTimestamp ),
			$title );

		$linkEntries = $this->constructLinkHeader(
			$first, $last, $memento, $next, $prev );

		$linkEntries .= $this->constructAdditionalLinkHeader(
			$this->mwrelurl, $title );
			*/
		echo "Memento With Header MOdifications not implemented yet!<br />\n";

	}
}
