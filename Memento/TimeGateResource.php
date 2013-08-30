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

class TimeGateResource extends MementoResource {

	// TODO: break this up to make it more legible,
	// it fails to obey the 50 line rule and looks frightening as a result
	/**
	 * Render the page
	 */
	public function render() {

		$first = array();
		$last = array();
		$memento = array();
		$next = array();
		$prev = array();

		$response = $this->out->getRequest()->response();
		$requestDatetime =
			$this->out->getRequest()->getHeader( 'ACCEPT-DATETIME' );

		/*
		 this is peculiar about Mediawiki, if we only use addVaryHeader
		 then it will not display for the 302 response, but if we don't
		 use addVaryHeader, it will not display for "friendly" error pages
		*/
		$response->header( 'Vary: Accept-Datetime', true );
		$this->out->addVaryHeader( 'Accept-Datetime' );

		$pageID = $this->title->getArticleID();
		$title = $this->title->getDBkey();

		if ( !$this->title->exists() ) {

			throw new MementoResourceException(
				'timegate-404-title', 'timegate',
				$this->out, $response, 404, array( $title )
			);
		}

		$mwMementoTimestamp = $this->parseRequestDateTime( $requestDatetime );

		$first = $this->convertRevisionData( $this->mwrelurl,
			$this->getFirstMemento( $this->dbr, $pageID ),
			$title );

		$last = $this->convertRevisionData( $this->mwrelurl,
			$this->getLastMemento( $this->dbr, $pageID ),
			$title );

		if ( $mwMementoTimestamp ) {
			$mwMementoTimestamp = $this->chooseBestTimestamp(
				$first['dt'], $last['dt'], $mwMementoTimestamp );

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
		}

		$linkEntries = $this->constructLinkHeader(
			$first, $last, $memento, $next, $prev );

		$linkEntries .= $this->constructOriginalLatestVersionLinkHeader(
			$this->mwrelurl, $title );

		$linkEntries .= $this->constructTimeMapLinkHeader(
			$this->mwrelurl, $title );

		$response->header( "Link: $linkEntries", true );

		if ( !$mwMementoTimestamp ) {
			throw new MementoResourceException(
				'timegate-400-date', 'timegate',
				$this->out, $response, 400,
				array( $requestDatetime, $first['uri'], $last['uri'] )
			);
		}

		$response->header( "HTTP", true, 302 );

		$mementoLocation = $memento['uri'];
		$response->header( "Location: $mementoLocation", true );

		// no output for a 302 response
		$this->out->disable();

	}

}
