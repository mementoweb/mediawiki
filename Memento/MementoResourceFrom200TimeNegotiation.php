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
 * This class implements the header alteration and entity alteration functions
 * used for 200-style Time Negotiation when an Accept-Datetime header is given
 * in the request.
 *
 * This class is named "MementoResource" rather than TimeGate because, even
 * though it performs TimeGate functions, the eventual object returned and
 * rendered is a Memento.
 *
 * This class is for when the URI-G=URI-R for 200-style in the Memento RFC.
 */
class MementoResourceFrom200TimeNegotiation extends MementoResource {

	/**
	 * alterHeaders
	 *
	 * Alter the headers for 200-style Time Negotiation when an Accept-Datetime
	 * header is given in the request.
	 */
	public function alterHeaders() {

		$out = $this->article->getContext()->getOutput();
		$request = $out->getRequest();
		$response = $request->response();
		$titleObj = $this->article->getTitle();

		$linkEntries = array();

		// if we exclude this Namespace, don't show folks the Memento relations
		// or conduct Time Negotiation
		if ( in_array( $titleObj->getNamespace(),
			$this->conf->get('ExcludeNamespaces') ) ) {

			$entry = '<http://mementoweb.org/terms/donotnegotiate>; rel="type"';
			array_push( $linkEntries, $entry );
		} else {
			$requestDatetime = $request->getHeader( 'ACCEPT-DATETIME' );

			$mwMementoTimestamp = $this->parseRequestDateTime(
				$requestDatetime );

			$pageID = $titleObj->getArticleID();

			// these database calls are required for time negotiation
			$first = $this->getFirstMemento( $this->dbr, $pageID );
			$last = $this->getLastMemento( $this->dbr, $pageID );

			$title = $this->getFullNamespacePageTitle( $titleObj );

			if ( $mwMementoTimestamp ) {
				$mwMementoTimestamp = $this->chooseBestTimestamp(
					$first['timestamp'], $last['timestamp'],
					$mwMementoTimestamp );
	
				$memento = $this->getCurrentMemento(
						$this->dbr, $pageID, $mwMementoTimestamp );
	
				$id = $memento['id'];
	
				$url = $this->getFullURIForID( $this->mwrelurl, $id, $title );
	
				$timegateuri = $this->getSafelyFormedURI( $this->mwrelurl, $title );
	
				$entry = $this->constructLinkRelationHeader( $timegateuri,
						'original latest-version timegate' );
				array_push( $linkEntries, $entry );
	
				if ( $this->conf->get('RecommendedRelations') ) {
	
					$entries = $this->generateRecommendedLinkHeaderRelations(
						$this->mwrelurl, $title, $first, $last );
	
					$linkEntries = array_merge( $linkEntries, $entries);
	
				} else {
					$entry = $this->constructTimeMapLinkHeader(
							$this->mwrelurl, $title );
					array_push( $linkEntries, $entry );
				}
	
				$mwMementoTimestamp = wfTimestamp(
					TS_RFC2822, $mwMementoTimestamp );
	
				$response->header(
					"Memento-Datetime: $mwMementoTimestamp", true );

				$response->header( "Content-Location: $url", true );
	
				$out->addVaryHeader( 'Accept-Datetime' );
	
				$this->setMementoOldID( $id );
			} else {
				$firsturi = $this->getFullURIForID(
					$this->mwrelurl, $first['id'], $title );
				$lasturi = $this->getFullURIForID(
					$this->mwrelurl, $first['id'], $title );

				throw new MementoResourceException(
					'timegate-400-date', 'timegate',
					$out, $response, 400,
					array( $requestDatetime, $firsturi, $lasturi )
					);
			}
		}

		$linkEntries = implode( ',', $linkEntries );

		$response->header( "Link: $linkEntries", true );
	}

	/**
	 * alterEntity
	 *
	 * This function alters the entity returned back for 200-style time
	 * negotiation when an Accept-Datetime header is present in the request.
	 * The existing entity is replaced with its Memento counterpart.
	 *
	 */
	public function alterEntity() {

			$out = $this->article->getContext()->getOutput();
			$titleObj = $this->article->getTitle();

			$pageID = $titleObj->getArticleID();

			$id = $this->getMementoOldID();

			// so that they get a warning if they try to edit the page
			$out->setRevisionId($id);

			$oldArticle = new Article( $title = $titleObj, $oldid = $id );
			$oldrev = $oldArticle->getRevisionFetched();

			// so we have the "Revision as of" text at the top of the page
			$this->article->setOldSubtitle($id);

			$oldArticleContent = $oldrev->getContent();

			$mementoArticleText =
				$oldArticleContent->getWikitextForTransclusion();

			$out->clearHTML();
			$out->addWikiText( $mementoArticleText );

	}
}
