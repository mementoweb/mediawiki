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
if ( ! defined( 'MEDIAWIKI' ) ) {
	echo "Not a valid entry point";
	exit( 1 );
}

/**
 * This class implements the header alteration and entity alteration functions
 * directly accessed Mementos, regardless of Time Negotiation style.
 *
 * This class is for the directly accessed URI-M mentioned in the Memento RFC.
 */
class MementoResourceDirectlyAccessed extends MementoResource {

	/**
	 * alterHeaders
	 *
	 * Put the Memento headers in place for this directly accessed Memento.
	 */
	public function alterHeaders() {

		$out = $this->article->getContext()->getOutput();
		$request = $out->getRequest();
		$response = $request->response();
		$titleObj = $this->article->getTitle();

		$linkEntries = array();

		// if we exclude this Namespace, don't show folks Memento relations
		if ( in_array( $titleObj->getNamespace(),
			$this->conf->get('ExcludeNamespaces') ) ) {

			$entry = '<http://mementoweb.org/terms/donotnegotiate>; rel="type"';
			$linkEntries[] = $entry;
		} else {
			$title = $this->getFullNamespacePageTitle( $titleObj );

			$mementoTimestamp =
				$this->article->getRevisionFetched()->getTimestamp();

			// convert for display
			$mementoDatetime = wfTimestamp( TS_RFC2822, $mementoTimestamp );

			$uri = $titleObj->getFullURL();

			$tguri = $this->getTimeGateURI( $title );

			if ( $uri == $tguri ) {
				$entry = $this->constructLinkRelationHeader( $tguri,
					'original latest-version timegate' );
				$linkEntries[] = $entry;
			} else {
				$entry = $this->constructLinkRelationHeader( $uri,
					'original latest-version' );
				$linkEntries[] = $entry;

				$entry = $this->constructLinkRelationHeader( $tguri,
					'timegate' );
				$linkEntries[] = $entry;
			}

			if ( $this->conf->get('RecommendedRelations') ) {

				// for performance, these database calls only occur
				// when $wgMementoRecommendedRelations is true
				$first = $this->getFirstMemento( $titleObj );
				$last = $this->getLastMemento( $titleObj );

				// TODO: Throw a 400-status error message if 
				// getFirstMemento/getLastMemento is null?
				// how would we have gotten here if titleObj was bad?

				$entries = $this->generateRecommendedLinkHeaderRelations(
					$titleObj, $first, $last );

				$linkEntries = array_merge( $linkEntries, $entries);

			} else  {
				$entry = $this->constructTimeMapLinkHeader( $title );
				$linkEntries[] = $entry;

			}

			$response->header( "Memento-Datetime:  $mementoDatetime", true );
		}

		$linkEntries = implode( ',', $linkEntries );

		$response->header( "Link: $linkEntries", true );
	}

	/**
	 * alterEntity
	 *
	 * No entity alterations are necessary for directly accessed Mementos.
	 *
	 */
	public function alterEntity() {
		// do nothing to the body
	}
}
