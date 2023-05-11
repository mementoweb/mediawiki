<?php
/**
 * This file is part of the Memento Extension to MediaWiki
 * https://www.mediawiki.org/wiki/Extension:Memento
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
		global $wgMementoIncludeNamespaces;

		$out = $this->article->getContext()->getOutput();
		$request = $out->getRequest();
		$response = $request->response();
		$titleObj = $this->article->getTitle();

		// if we exclude this Namespace, don't show folks Memento relations
		if ( !in_array( $titleObj->getNamespace(), $wgMementoIncludeNamespaces ) ) {
			$entry = '<http://mementoweb.org/terms/donotnegotiate>; rel="type"';
			$out->addLinkHeader( $entry );

		} else {

			$title = $this->getFullNamespacePageTitle( $titleObj );

			$mementoTimestamp = $this->article->getRevisionFetched()->getTimestamp();

			// convert for display
			$mementoDatetime = wfTimestamp( TS_RFC2822, $mementoTimestamp );

			$uri = $titleObj->getFullURL();

			$tguri = $this->getTimeGateURI( $title );

			$entry = $this->constructLinkRelationHeader( $uri,
				'original latest-version' );
			$out->addLinkHeader( $entry );

			$entry = $this->constructLinkRelationHeader( $tguri,
				'timegate' );
			$out->addLinkHeader( $entry );

			$first = $this->getFirstMemento( $titleObj );
			$last = $this->getLastMemento( $titleObj );

			// TODO: Throw a 400-status error message if
			// getFirstMemento/getLastMemento is null?
			// how would we have gotten here if titleObj was bad?

			$entries = $this->generateRecommendedLinkHeaderRelations(
				$titleObj, $first, $last );

			foreach ( $entries as $value ) {
				$out->addLinkHeader( $value );
			}

			$response->header( "Memento-Datetime:  $mementoDatetime", true );
		}
	}

}
