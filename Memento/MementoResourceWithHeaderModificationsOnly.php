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

class MementoResourceWithHeaderModificationsOnly extends MementoResource {

	/**
	 * Render the page
	 */
	public function alterHeaders() {

		$out = $this->article->getContext()->getOutput();
		$request = $out->getRequest();
		$response = $request->response();
		$titleObj = $this->article->getTitle();

		// if we exclude this Namespace, don't show folks Memento relations
		if ( in_array( $titleObj->getNamespace(),
			$this->conf->get('ExcludeNamespaces') ) ) {

			$linkEntries =
				'<http://mementoweb.org/terms/donotnegotiate>; rel="type"';
		} else {
			$title = $this->getFullNamespacePageTitle( $titleObj );
			$pageID = $titleObj->getArticleID();
			$oldID = $this->article->getOldID();

			$mementoInfo = $this->getInfoForThisMemento( $this->dbr, $oldID );
			$mementoInfoID = $mementoInfo['id'];
			$mementoDatetime = $mementoInfo['timestamp'];

			$memento = $this->convertRevisionData( $this->mwrelurl,
				$this->getCurrentMemento(
					$this->dbr, $mementoInfoID, $mementoDatetime ),
				$title );

			$timegateuri = $this->getTimeGateURI( $this->mwrelurl, $title );
			$originaluri = $this->getOriginalURI( $this->mwrelurl, $title );

			if ( $timegateuri == $originaluri ) {
				$linkEntries =
					$this->constructLinkRelationHeader( $timegateuri,
						'original latest-version timegate' ) . ',';
			} else {
				$linkEntries =
					$this->constructLinkRelationHeader( $timegateuri,
						'timegate' ) . ',';
				$linkEntries .=
					$this->constructLinkRelationHeader( $originaluri,
						'original latest-version' ) . ',';
			}

			if ( $this->conf->get('RecommendedRelations') ) {

				$first = $this->convertRevisionData( $this->mwrelurl,
					$this->getFirstMemento( $this->dbr, $mementoInfoID ),
					$title );

				$last = $this->convertRevisionData( $this->mwrelurl,
					$this->getLastMemento( $this->dbr, $mementoInfoID ),
					$title );

				$next = $this->convertRevisionData( $this->mwrelurl,
					$this->getNextMemento(
						$this->dbr, $mementoInfoID, $mementoDatetime ),
					$title );

				$prev = $this->convertRevisionData( $this->mwrelurl,
					$this->getPrevMemento(
						$this->dbr, $mementoInfoID, $mementoDatetime ),
					$title );

				$linkEntries .=
					$this->constructTimeMapLinkHeaderWithBounds(
						$this->mwrelurl, $title,
						$first['dt'], $last['dt'] )
					. ',';

				$linkEntries .= $this->constructLinkHeader(
					$first, $last, $memento, $next, $prev );

			} else  {
				$linkEntries .=
					$this->constructTimeMapLinkHeader( $this->mwrelurl, $title )
					. ',';

				$linkEntries .= $this->constructMementoLinkHeaderEntry(
					$this->mwrelurl, $title, $oldID,
					$memento['dt'], 'memento' );
			}

			// convert for display
			$mementoDatetime = wfTimestamp( TS_RFC2822, $mementoDatetime );

			$response->header( "Memento-Datetime:  $mementoDatetime", true );
		}

		$response->header( "Link: $linkEntries", true );
	}

	public function alterEntity() {
		// do nothing to the body
	}
}
