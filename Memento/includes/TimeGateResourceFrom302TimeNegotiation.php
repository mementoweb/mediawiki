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
 * used for 302-style Time Negotiation when an Accept-Datetime header is
 * given in the request.  The response is heavily altered, replacing the
 * standard Mediawiki page load with a 302 redirect.
 *
 * This class is named "TimeGateResource" because the response is a TimeGate.
 *
 * This class is for when the URI-G=URI-R for 302-style in the Memento RFC.
 */
class TimeGateResourceFrom302TimeNegotiation extends MementoResource {

	/**
	 * alterHeaders
	 *
	 * Create the 302 redirect response for this Mediawiki Page.  All output
	 * is disabled once the headers are constructed so that there is no entity
	 * in the 302 response.
	 *
	 */
	public function alterHeaders() {
		global $wgMementoIncludeNamespaces;

		$out = $this->article->getContext()->getOutput();
		$request = $out->getRequest();
		$response = $request->response();
		$titleObj = $this->article->getTitle();

		$linkEntries = [];

		// if we exclude this Namespace, don't show folks the Memento relations
		// or conduct Time Negotiation
		if ( !in_array( $titleObj->getNamespace(), $wgMementoIncludeNamespaces ) ) {
			$entry = '<http://mementoweb.org/terms/donotnegotiate>; rel="type"';
			$linkEntries[] = $entry;
		} else {

			$negotiator = new TimeNegotiator( $this );

			$linkEntries = $negotiator->getLinkRelationEntries();
			$url = $negotiator->getLocationURI();

			// this does not work for some reason, possibly because
			// of the disable() below?
			// $out->addVaryHeader( 'Accept-Datetime' );

			// workaround for addVaryHeader
			$varyEntries = explode( ':', $out->getVaryHeader() );
			$varyEntries = $varyEntries[1];
			$response->header( "Vary: $varyEntries,Accept-Datetime", true );

			// Tried this, but it didn't generate a 302 response
			// $out->redirect($url, 302);
			// the following two lines are a workaround
			$response->header( "Location: $url", true );
			$out->setStatusCode( 302 );

			$out->disable();
		}

		$linkEntries = implode( ',', $linkEntries );

		$response->header( "Link: $linkEntries", true );
	}

}
