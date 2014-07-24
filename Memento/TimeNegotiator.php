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
 * This class centralizes Time Negotiation.  It uses a Memento Resource as
 * input, manipulates that resource to its liking, then stores output
 * available in a variety of getters.
 */
class TimeNegotiator {

	/**
	 * @var MementoResource $mementoResource - resource to operate on
	 */
	private $mementoResource;

	/**
	 * @var string $locationURI - location URI for header use
	 */
	private $locationURI;

	/**
	 * @var array $linkRelations - array containing link relation text
	 */
	private $linkRelations;

	/**
	 * @var string $mementoDatetime - datetime of the memento in RFC2822
	 */
	private $mementoDatetime;

	/**
	 * @var string $mementoId - oldID of the Memento page
	 */
	private $mementoId;

	/**
	 * Constructor for TimeNegotiator
	 *
	 * @param MementoResource $mementoResource object to work on
	 *
	 */
	public function __construct( $mementoResource ) {

		$this->mementoResource = $mementoResource;

		$this->linkRelations = array();

		$this->negotiate();
	}

	/**
	 * negotiate
	 *
	 * Time negotiation function, intended only to be called from the
	 * constructor. Has class-wide side effects.
	 *
	 */
	private function negotiate() {

		$mr = $this->mementoResource;
		$article = $mr->getArticleObject();
		$out = $article->getContext()->getOutput();
		$request = $out->getRequest();
		$response = $request->response();
		$titleObj = $article->getTitle();

		$requestDatetime = $request->getHeader( 'ACCEPT-DATETIME' );

		$mwMementoTimestamp = $mr->parseRequestDateTime(
			$requestDatetime );

		$pageID = $titleObj->getArticleID();

		// these database calls are required for time negotiation
		$first = $mr->getFirstMemento( $titleObj );
		$last = $mr->getLastMemento( $titleObj );

		// TODO: Throw a 400-status error message if
		// getFirstMemento/getLastMemento is null?
		// how would we have gotten here if titleObj was bad?

		$title = $mr->getFullNamespacePageTitle( $titleObj );

		$entries = $mr->generateRecommendedLinkHeaderRelations(
			$titleObj, $first, $last );


		$this->linkRelations = array_merge(
			$this->linkRelations, $entries );

		if ( $mwMementoTimestamp ) {

			$bestTimestamp = $mr->chooseBestTimestamp(
				wfTimestamp( TS_MW, $first['timestamp'] ),
				wfTimestamp( TS_MW, $last['timestamp'] ),
				$mwMementoTimestamp );

			$outputTS = $bestTimestamp;

			$memento = $mr->getCurrentMemento( $pageID, $bestTimestamp );

			$id = $memento['id'];

			$uri = $titleObj->getFullURL();

			$tguri = $mr->getTimeGateURI( $title );

			if ( $uri == $tguri ) {
				$entry = $mr->constructLinkRelationHeader( $tguri,
					'original latest-version timegate' );
				$this->linkRelations[] = $entry;
			} else {
				$entry = $mr->constructLinkRelationHeader( $uri,
					'original latest-version' );
				$this->linkRelations[] = $entry;
			}

			// storage for caller
			$this->mementoDatetime = wfTimestamp( TS_RFC2822, $bestTimestamp );
			$this->locationURI = $titleObj->getFullURL( array( "oldid" => $id ) );
			$this->mementoId = $memento['id'];

		} else {
			$firsturi = $titleObj->getFullURL( array( "oldid" => $first['id'] ) );
			$lasturi = $titleObj->getFullURL( array( "oldid" => $last['id'] ) );

			$linkEntries = implode( ',', $this->linkRelations );

			// this does not work for traditional errors, possibly because
			// of the disable() later, but does work for friendly
			// errors
			$out->addVaryHeader( 'Accept-Datetime' );

			// workaround for addVaryHeader for traditional errors
			//$varyEntries = explode( ':', $out->getVaryHeader() );
			//$varyEntries = $varyEntries[1];
			//$response->header( "Vary: $varyEntries,Accept-Datetime", true );
			//$response->header( 'Link: ' . $linkEntries, true );

			throw new ErrorPageError( 'timegate-title', 'timegate-400-date', 
				array( $requestDatetime, $firsturi, $lasturi ) );

		}
	}

	/**
	 * getLocationURI
	 *
	 * Retrieve the value of the location URI, for use in Content-Location
	 * or Location, depending on the Time Negotiation Pattern.
	 *
	 * @return string $locationURI
	 */
	public function getLocationURI() {
		return $this->locationURI;
	}

	/**
	 * getLinkRelationEntries
	 *
	 * Retrieve an array containing the link relation entries, for use
	 * in a Link header.
	 *
	 * @return array $linkRelations
	 */
	public function getLinkRelationEntries() {
		return $this->linkRelations;
	}

	/**
	 * getMementoDateTime
	 *
	 * Retrieve the RFC2822 Memento DateTime, for use in any HTTP header.
	 *
	 * @return string $mementoDatetime
	 */
	public function getMementoDateTime() {
		return $this->mementoDatetime;
	}

}
