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
 * This abstract class is the parent of all MementoResource types.
 * As such, it contains the methods used by all of the Memento Pages.
 *
 */
abstract class MementoResource {

	/**
	 * Constructor for MementoResource and its children
	 *
	 * @param DatabaseBase $db
	 * @param Article $article
	 *
	 */
	public function __construct( DatabaseBase $db, Article $article ) {

		$this->db = $db;
		$this->article = $article;

	}

	/**
	 * @var DatabaseBase $db DatabaseBase object for Memento Extension
	 */
	protected $db;

	/**
	 * @var Article $article Article Object of this Resource
	 */
	protected $article;

	/**
	 * getArticleObject
	 *
	 * Getter for Article Object used in constructor.
	 *
	 * @return Article
	 */
	public function getArticleObject() {
		return $this->article;
	}

	/**
	 * fetchMementoFromDatabase
	 *
	 * Make the actual database call.
	 *
	 * @param string $sqlCondition the conditional statement
	 * @param string $sqlOrder order of the data returned (e.g. ASC, DESC)
	 *
	 * @return array associative array with id and timestamp keys
	 */
	public function fetchMementoFromDatabase( $sqlCondition, $sqlOrder ) {

		$db = $this->db;

		// TODO: use selectRow instead
		// tried selectRow here, but it returned nothing
		$results = $db->select(
			'revision',
			array( 'rev_id', 'rev_timestamp' ),
			$sqlCondition,
			__METHOD__,
			array( 'ORDER BY' => $sqlOrder, 'LIMIT' => '1' )
			);

		$row = $db->fetchObject( $results );

		$revision = array();

		if ( $row ) {
			$revision['id'] = $row->rev_id;
			$revision['timestamp'] = wfTimestamp(
				TS_RFC2822, $row->rev_timestamp );
		}

		return $revision;

	}

	/**
	 * getFirstMemento
	 *
	 * Extract the first memento from the database.
	 *
	 * @param Title $title
	 *
	 * @return array associative array with id and timestamp keys
	 */
	public function getFirstMemento( Title $title ) {
		$revision = array();

		$firstRevision = $title->getFirstRevision();

		if ( $firstRevision != null ) {

			$revision['timestamp'] = 
				wfTimestamp( TS_RFC2822, $firstRevision->getTimestamp() );
			$revision['id'] = $firstRevision->getId();

		}

		return $revision;
	}

	/**
	 * getLastMemento
	 *
	 * Extract the last memento from the database.
	 *
	 * @param Title $title
	 *
	 * @return array associative array with id and timestamp keys
	 */
	public function getLastMemento( Title $title ) {

		$revision = array();

		$lastRevision = Revision::newFromTitle( $title );

		if ( $lastRevision != null ) {

			$revision['timestamp'] = 
				wfTimestamp( TS_RFC2822, $lastRevision->getTimestamp() );
			$revision['id'] = $lastRevision->getId();

		}

		return $revision;
	}

	/**
	 * getCurrentMemento
	 *
	 * Extract the memento that best matches from the database.
	 *
	 * @param integer $pageID page identifier
	 * @param string $pageTimestamp timestamp used for finding the best memento
	 *
	 * @return array associative array with id and timestamp keys
	 */
	public function getCurrentMemento( $pageID, $pageTimestamp ) {

		$sqlCondition = 
			array(
				'rev_page' => $pageID,
				'rev_timestamp<=' . $this->db->addQuotes( $pageTimestamp )
				);
		$sqlOrder = 'rev_timestamp DESC';

		return $this->fetchMementoFromDatabase(
			$sqlCondition, $sqlOrder );
	}

	/**
	 * getNextMemento
	 *
	 * Extract the last memento from the database.
	 *
	 * @param integer $pageID page identifier
	 * @param string $pageTimestamp timestamp used for finding the last memento
	 *
	 * @return array associative array with id and timestamp keys
	 */
	public function getNextMemento( $pageID, $pageTimestamp ) {

		$sqlCondition = 
			array(
				'rev_page' => $pageID,
				'rev_timestamp>' . $this->db->addQuotes( $pageTimestamp )
				);
		$sqlOrder = 'rev_timestamp ASC';

		return $this->fetchMementoFromDatabase(
			$sqlCondition, $sqlOrder );
	}

	/**
	 * getPrevMemento
	 *
	 * Extract the last memento from the database.
	 *
	 * @param integer $pageID page identifier
	 * @param string $pageTimestamp timestamp used for finding the last memento
	 *
	 * @return array associative array with id and timestamp keys
	 */
	public function getPrevMemento( $pageID, $pageTimestamp ) {

		$sqlCondition = 
			array(
				'rev_page' => $pageID,
				'rev_timestamp<' . $this->db->addQuotes( $pageTimestamp )
				);
		$sqlOrder = 'rev_timestamp DESC';

		return $this->fetchMementoFromDatabase(
			$sqlCondition, $sqlOrder );
	}

	/**
	 * parseRequestDateTime
	 *
	 * Take in the RFC2822 datetime and convert it to the format used by
	 * Mediawiki.
	 *
	 * @param string $requestDateTime
	 *
	 * @return string $dt datetime in mediawiki database format
	 */
	public function parseRequestDateTime( $requestDateTime ) {

		$dt = wfTimestamp( TS_MW, $requestDateTime );

		return $dt;
	}

	/**
	 * chooseBestTimestamp
	 *
	 * If the requested time is earlier than the first memento,
	 * the first memento will be returned.
	 * If the requested time is past the last memento, or in the future,
	 * the last memento will be returned.
	 * Otherwise, go with the one we've got because the future database call
	 * will get the nearest memento.
	 *
	 * @param string $firstTimestamp the first timestamp for which we have a memento
	 *				formatted in the TS_MW format
	 * @param string $lastTimestamp the last timestamp for which we have a memento
	 *				formatted in the TS_MW format
	 * @param string $givenTimestamp the timestamp given by the request header
	 *				formatted in the TS_MW format
	 *
	 * @return string $chosenTimestamp the timestamp to use
	 */
	public function chooseBestTimestamp(
		$firstTimestamp, $lastTimestamp, $givenTimestamp ) {

		$chosenTimestamp = null;

		if ( $givenTimestamp < $firstTimestamp ) {
			$chosenTimestamp = $firstTimestamp;
		} elseif ( $givenTimestamp > $lastTimestamp ) {
			$chosenTimestamp = $lastTimestamp;
		} else {
			$chosenTimestamp = $givenTimestamp;
		}

		return $chosenTimestamp;
	}

	/**
	 * constructMementoLinkHeaderRelationEntry
	 *
	 * This creates the entry for a memento for the HTTP Link Header for
	 * communication with a Memento client.
	 * This is not intended to be used for HTML or any HTTP entity format.
	 *
	 * @param string $url the URL of the given page
	 * @param string $timestamp the timestamp of this Memento, in RFC 1123 format
	 * @param string $relation the relation type of this Memento
	 *
	 * @return string full Memento Link header entry
	 */
	public function constructMementoLinkHeaderRelationEntry(
		$url, $timestamp, $relation ) {

		$entry = '<' . $url . '>; rel="' . $relation . '"; datetime="' .
			$timestamp . '"';

		return $entry;
	}

	/**
	 * constructTimeMapLinkHeaderWithBounds
	 *
	 * This creates the entry for a timemap in the HTTP Link Header for
	 * communication with a Memento client.  This special version
	 * of this function allows one to specify additional from and until
	 * relations for use in the HTTP Link Header.
	 * This is not intended to be used for HTML or any HTTP entity format.
	 *
	 * @param string $title the title string of the given page
	 * @param string $from the from timestamp for the TimeMap, in RFC 1123 format
	 * @param string $until the until timestamp for the TimeMap, in RFC 1123 format
	 *
	 * @return string full Memento TimeMap relation with from and until
	 */
	public function constructTimeMapLinkHeaderWithBounds(
		$title, $from, $until ) {

		$entry = $this->constructTimeMapLinkHeader( $title );

		$entry .= "; from=\"$from\"; until=\"$until\"";

		return $entry;
	}

	/**
	 * constructTimeMapLinkHeader
	 *
	 * This creates the entry for timemap in the HTTP Link Header for
	 * communication with a Memento client.
	 * This is not intended to be used for HTML or any HTTP entity format.
	 *
	 * @param string $title the title string of the given page
	 *
	 * @return string Memento TimeMap relation
	 */
	public function constructTimeMapLinkHeader( $title ) {

		$uri = SpecialPage::getTitleFor( 'TimeMap', $title )->getFullURL();

		$entry = '<' . $uri .  '>; rel="timemap"; type="application/link-format"';

		return $entry;
	}

	/**
	 * getFullNamespacePageTitle
	 *
	 * This function returns the namespace:title string from the URI
	 * corresponding to this resource.  It is meant to be the URI version,
	 * without spaces, hence we cannot use Title::getPrefixedText.
	 *
	 * @param Title $titleObj title object corresponding to this resource
	 *
	 * @return string $title the URL-like (without spaces) namespace:title string for the given page
	 */
	public function getFullNamespacePageTitle( Title $titleObj ) {
		$title = $titleObj->getDBkey();
		$namespace = $titleObj->getNsText();

		if ( $namespace ) {
			$title = "$namespace:$title";
		}

		return $title;
	}

	/**
	 * constructLinkRelationHeader
	 *
	 * This creates a link header entry for the given URI, with no
	 * extra information, just URL and relation.
	 * This creates the entry for the HTTP Link Header for
	 * communication with a Memento client.
	 * It is not intended to be used for HTML or any HTTP entity format.
	 *
	 * @param string $url the URL of the relation
	 * @param string $relation the relation type for this Link header entry
	 *
	 * @return string relation
	 */
	public function constructLinkRelationHeader( $url, $relation ) {
		return '<' . $url . '>; rel="' . $relation . '"';
	}

	/**
	 * generateRecommendedLinkHeaderRelations
	 *
	 * This function generates the recommended link header relations,
	 * handling cases such as 'first memento' and 'last memento' vs.
	 * 'first last memento', etc.
	 *
	 * @param Title $titleObj - the article title object
	 * @param array $first associative array containing info on the first memento
	 * 					with the keys 'timestamp' and 'id'
	 * @param array $last associative array containing info on the last memento
	 * 					with the keys 'timestamp' and 'id'
	 *
	 * @return array array of link relations
	 */
	public function generateRecommendedLinkHeaderRelations(
		Title $titleObj, $first, $last ) {

		$linkRelations = array();

		$title = $this->getFullNamespacePageTitle( $titleObj );

		$entry = $this->constructTimeMapLinkHeaderWithBounds(
			$title, $first['timestamp'], $last['timestamp'] );
		$linkRelations[] = $entry;

		$firsturi = $titleObj->getFullURL( array( "oldid" => $first['id'] ) );
		$lasturi = $titleObj->getFullURL( array( "oldid" => $last['id'] ) );

		if ( $first['id'] == $last['id'] ) {
			$entry = $this->constructMementoLinkHeaderRelationEntry(
				$firsturi, $first['timestamp'], 'first last memento' );
			$linkRelations[] = $entry;
		} else {
			$entry = $this->constructMementoLinkHeaderRelationEntry(
				$firsturi, $first['timestamp'], 'first memento' );
			$linkRelations[] = $entry;
			$entry = $this->constructMementoLinkHeaderRelationEntry(
				$lasturi, $last['timestamp'], 'last memento' );
			$linkRelations[] = $entry;
		}

		return $linkRelations;
	}

	/**
	 * getTimeGateURI
	 *
	 * Get the URI for the TimeGate.
	 *
	 * @param string $title wiki page title text
	 *
	 * @return string
	 */
	public function getTimeGateURI( $title ) {

		return SpecialPage::getTitleFor( 'TimeGate', $title )->getFullURL();

	}

	/**
	 * mementoPageResourceFactory
	 *
	 * A factory for creating the correct MementoPageResource type.
	 *
	 * @param DatabaseBase $db passed to constructor
	 * @param Article $article passed to constructor
	 * @param integer $oldID revision ID used in decision
	 *
	 * @return MementoResource the correct instance of MementoResource base	on $oldID
	 */
	public static function mementoPageResourceFactory( DatabaseBase $db, $article, $oldID ) {

		$resource = null;

		if ( $oldID == 0 ) {

			$resource = new OriginalResourceDirectlyAccessed( $db, $article );

		} else {

			// we are requesting a Memento directly (an oldID URI)
			$resource = new MementoResourceDirectlyAccessed( $db, $article );

		}

		return $resource;
	}

	/**
	 * fixTemplate
	 *
	 * This code ensures that the version of the Template that was in existence
	 * at the same time as the Memento gets loaded and displayed with the
	 * Memento.
	 *
	 * @fixme make this compatible with parser cache
	 * @param Title $title
	 * @param Parser $parser
	 * @param integer $id
	 *
	 * @return array containing the text, finalTitle, and deps
	 */
	public function fixTemplate( Title $title, Parser $parser, &$id ) {

		// stopgap measure until we can find a better way
		// to work with parser cache
		$parser->disableCache();

		$request = $parser->getUser()->getRequest();

		if ( $request->getHeader( 'ACCEPT-DATETIME' ) ) {

			$requestDatetime = $request->getHeader( 'ACCEPT-DATETIME' );

			$mwMementoTimestamp = $this->parseRequestDateTime(
				$requestDatetime );

			$firstRev = $title->getFirstRevision();

			// if the template no longer exists, return gracefully
			if ( $firstRev != null ) {

				if ( $firstRev->getTimestamp() < $mwMementoTimestamp ) {

					$pgID = $title->getArticleID();

					$this->db->begin();

					$res = $this->db->selectRow(
						'revision',
						array( 'rev_id' ),
						array(
							'rev_page' => $pgID,
							'rev_timestamp <=' .
								$this->db->addQuotes( $mwMementoTimestamp )
							),
						__METHOD__,
						array( 'ORDER BY' => 'rev_id DESC', 'LIMIT' => '1' )
					);

					$id = $res->rev_id;

				} else {

					// if we get something prior to the first memento, just
					// go with the first one
					$id = $firstRev->getId();
				}
			}
		}
	}


	/**
	 * alterHeaders
	 *
	 * This function is used to alter the headers of the outgoing response,
	 * and must be implemented by the MementoResource implementation.
	 * It is expected to be called from the ArticleViewHeader hook.
	 *
	 */
	abstract public function alterHeaders();

}
