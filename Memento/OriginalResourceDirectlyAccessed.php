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
 * This class implements the header alteration and entity alteration functions
 * used for any style of Time Negotiation when an Accept-Datetime header is NOT
 * given in the request.
 *
 * This class is for the direclty accessed URI-R mentioned in the Memento RFC.
 */
class OriginalResourceDirectlyAccessed extends MementoResource {

	/**
	 * alterHeaders
	 *
	 * Alter the headers for 200-style Time Negotiation when an Accept-Datetime
	 * header is NOT given in the request.
	 */
	public function alterHeaders() {


		global $wgMementoIncludeNamespaces;

		$out = $this->article->getContext()->getOutput();
		$titleObj = $this->article->getTitle();

		$title = $this->getFullNamespacePageTitle( $titleObj );

		$linkEntries = array();


		// if we exclude this Namespace, don't show folks the Memento relations
		if ( ! in_array( $titleObj->getNamespace(), $wgMementoIncludeNamespaces ) ) {

			$entry = '<http://mementoweb.org/terms/donotnegotiate>; rel="type"';
			$linkEntries[] = $entry;

		} else {

			$uri = $titleObj->getFullURL();

			$tguri = $this->getTimeGateURI( $title );

			$entry = $this->constructLinkRelationHeader( $uri,
				'original latest-version' );
			$linkEntries[] = $entry;

			$entry = $this->constructLinkRelationHeader( $tguri,
				'timegate' );
			$linkEntries[] = $entry;

			$first = $this->getFirstMemento( $titleObj );
			$last = $this->getLastMemento( $titleObj );

			// TODO: Throw a 400-status error message if
			// getFirstMemento/getLastMemento is null?
			// how would we have gotten here if titleObj was bad?

			$entries = $this->generateRecommendedLinkHeaderRelations(
				$titleObj, $first, $last );

			$linkEntries = array_merge( $linkEntries, $entries );

			$linkEntries = implode( ',', $linkEntries );
		}


		//$response->header( 'Link: ' . $linkEntries, true );
		$out->addLinkHeader( $linkEntries );
	}

}
