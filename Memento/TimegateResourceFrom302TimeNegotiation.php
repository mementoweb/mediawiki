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

class TimeGateResourceFrom302TimeNegotiation extends MementoResource {

	public function render() {

		// if we exclude this Namespace, don't show folks the Memento relations
		// or conduct Time Negotiation
		if ( in_array( $this->title->getNamespace(),
			$this->conf->get('ExcludeNamespaces') ) ) {

			$linkEntries =
				'<http://mementoweb.org/terms/donotnegotiate>; rel="type"';
			$this->out->getRequest()->response()->header(
				"Link: $linkEntries", true );
		} else {
			$this->out->disable();

			$requestDatetime = $this->out->getRequest()->getHeader(
				'ACCEPT-DATETIME');

			$mwMementoTimestamp = $this->parseRequestDateTime( $requestDatetime );

			$pageID = $this->title->getArticleID();

			$first = $this->getFirstMemento( $this->dbr, $pageID );

			$last = $this->getLastMemento( $this->dbr, $pageID );

			$mwMementoTimestamp = $this->chooseBestTimestamp(
				$first['timestamp'], $last['timestamp'], $mwMementoTimestamp );

			$memento = $this->getCurrentMemento(
					$this->dbr, $pageID, $mwMementoTimestamp );

			$id = $memento['id'];

			// so that they get a warning if they try to edit the page
			$this->out->setRevisionId($memento['id']);

			$title = $this->getFullNamespacePageTitle();

			$url = $this->getFullURIForID( $this->mwrelurl, $id, $title );

			# the following headers comply with Pattern 1.2 of the Memento RFC
			$this->out->getRequest()->response()->header(
				"Location: $url", true );

			$timegateuri = $this->getTimeGateURI( $this->mwrelurl, $title );

			$linkEntries =
				$this->constructLinkRelationHeader( $timegateuri,
					'original latest-version timegate' ) . ',';

			if ( $this->conf->get('RecommendedRelations') ) {
				$linkEntries .=
					$this->constructTimeMapLinkHeaderWithBounds(
						$this->mwrelurl, $title,
						$first['timestamp'], $last['timestamp'] )
					. ',';

				$linkEntries .= $this->constructMementoLinkHeaderEntry(
					$this->mwrelurl, $title, $first['id'],
					$first['timestamp'], 'memento first' ) . ',';

				$linkEntries .= $this->constructMementoLinkHeaderEntry(
					$this->mwrelurl, $title, $last['id'],
					$last['timestamp'], 'memento last' ) . ',';

			} else {
				$linkEntries .=
					$this->constructTimeMapLinkHeader( $this->mwrelurl, $title );
			}

			$mwMementoTimestamp = wfTimestamp( TS_RFC2822, $mwMementoTimestamp );

			$this->out->addVaryHeader( 'Accept-Datetime' );

			$this->out->getRequest()->response()->header(
				"Link: $linkEntries", true );

			$this->out->getRequest()->response()->header("HTTP", true, 302);

			$this->out->clearHTML();

		}

	}
}
