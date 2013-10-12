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
class OriginalResourceWithTimeNegotiation extends MementoResource {

	/**
	 * Render the page
	 */
	public function alterHeaders() {

		$out = $this->article->getContext()->getOutput();
		$request = $out->getRequest();
		$response = $request->response();
		$titleObj = $this->article->getTitle();

		$requestURL = $request->getFullRequestURL();
		$title = $this->getFullNamespacePageTitle( $titleObj );

		// if we exclude this Namespace, don't show folks the Memento relations
		if ( in_array( $titleObj->getNamespace(),
			$this->conf->get('ExcludeNamespaces') ) ) {

			$linkEntries =
				'<http://mementoweb.org/terms/donotnegotiate>; rel="type"';
		} else {

			$timegateuri = $this->getTimeGateURI( $this->mwrelurl, $title );

			$timeGateLinkEntry =
				$this->constructLinkRelationHeader( $timegateuri,
					'original latest-version timegate' );

			$timeMapLinkEntry = $this->constructTimeMapLinkHeader(
				$this->mwrelurl, $title );

			$linkEntries = implode( ',',
				array( $timeGateLinkEntry, $timeMapLinkEntry ) );

			$out->addVaryHeader( 'Accept-Datetime' );
		}

		$response->header( 'Link: ' . $linkEntries, true );
	}

	public function alterEntity() {
		// do nothing to the body
	}
}
