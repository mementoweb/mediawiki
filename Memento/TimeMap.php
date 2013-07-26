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

// ensure that the script can't be executed outside of Mediawiki
if ( ! defined( 'MEDIAWIKI' ) ) {
	echo "Not a valid entry point";
	exit(1);
}

/**
 *
 * Special Page Implementation of a Memento TimeMap
 * @see http://mementoweb.org
 *
 * This class handles the entry point from Mediawiki and performs
 * the mediation over the real work.  The goal is to separate
 * the Mediawiki setup code from the Memento code as much as possible
 * for clarity, testing, maintainability, etc.
 *
 */
class TimeMap extends SpecialPage {

	/**
	 * Constructor
	 */
	function __construct() {
		$this->ascendingURLPattern = "^[0-9]+\/1\/";
		$this->descendingURLPattern = "^[0-9]+\/-1\/";

		parent::__construct( "TimeMap" );
	}

	/**
	 * is Full
	 *
	 * Return true if the URL is for a full TimeMap.
	 */
	public function isFull($urlparam) {
		return ( substr($urlparam, 0, 4 ) == "http" );
	}

	/**
	 * isPivotAscending
	 *
	 * Return true if the URL is for a TimeMap ascending from a pivot.
	 */
	public function isPivotAscending($urlparam) {
		return (
			preg_match( "/$this->ascendingURLPattern/", $urlparam ) == 1 );
	}

	/**
	 * isPivotDescending
	 *
	 * Return true if the URL is for a TimeMap descending from a pivot.
	 */
	public function isPivotDescending($urlparam) {
		return (
			preg_match( "/$this->descendingURLPattern/", $urlparam ) == 1 );
	}

	/**
	 * timeMapFactory
	 *
	 * This function determines which TimeMap object behavior we will get
	 * based on the input.
	 */
	public function timeMapFactory( $out, $config, $dbr, $urlparam, $title ) {

		if ( $this->isFull( $urlparam ) ) {
			$tm = new TimeMapFullResource(
				$out, $config, $dbr, $title, $urlparam, null );
		} elseif ( $this->isPivotAscending( $urlparam ) ) {
			$tm = new TimeMapPivotAscendingResource(
				$out, $config, $dbr, $title, $urlparam, null );
		} elseif ( $this->isPivotDescending( $urlparam ) ) {
			$tm = new TimeMapPivotDescendingResource(
				$out, $config, $dbr, $title, $urlparam, null );
		} else {
			$titleMessage = 'timemap';
			$textMessage = 'timemap-400-date';
			$server = $config->get('Server');
			$waddress = str_replace(
				'$1', '', $config->get('ArticlePath') );
			$title = str_replace( $server . $waddress, "", $urlparam );
			$response = $this->getOutput()->getRequest()->response();

			throw new MementoResourceException(
				$textMessage, $titleMessage,
				$out, $response, 400, array( )
			);
		}

		return $tm;
	}

	/**
	 * getTitle
	 *
	 * This function extracts the Title from the URL
	 */
	public function getPageTitle( $server, $waddress, $urlparam ) {

		if ( $this->isFull( $urlparam ) ) {
			$title = str_replace( $server . $waddress, "", $urlparam );
		} elseif ( $this->isPivotAscending( $urlparam ) ) {
			$title = preg_replace(
				'/' . $this->ascendingURLPattern .
				str_replace( "/", "\\/", $server ) .
				str_replace( "/", "\\/", $waddress ) .
				'/',
				"", $urlparam );
		} elseif ( $this->isPivotDescending( $urlparam ) ) {
			$title = preg_replace(
				'/' . $this->descendingURLPattern .
				str_replace( "/", "\\/", $server ) .
				str_replace( "/", "\\/", $waddress ) .
				'/',
				"", $urlparam );
		} else {
			$title = null; // let the 500 rain down
		}

		return $title;
	}

	/**
	 * The init function that is called by mediawiki when loading this
	 * SpecialPage.
	 *
	 * @param $urlpar: string; the title parameter returned by Mediawiki
	 *				which, in this case, is the URI for which we want TimeMaps
	 */
	public function execute($urlparam) {

		$out = $this->getOutput();
		$this->setHeaders();

		if ( !$urlparam ) {
			$out->addHTML( wfMessage( 'timemap-welcome-message' )->parse() );
			return;
		} else {

			$config = new MementoConfig();
			$dbr = wfGetDB( DB_SLAVE );

			$server = $config->get('Server');
			$waddress = str_replace( '$1', '', $config->get('ArticlePath') );
			$title = $this->getPageTitle( $server, $waddress, $urlparam );

			$title = Title::newFromText( $title );

			try {
				$page = $this->timeMapFactory(
					$out, $config, $dbr, $urlparam, $title );
				$page->render();
			} catch (MementoResourceException $e) {
				MementoResource::renderError(
					$out, $e, $config->get('ErrorPageType') );
			}

		}

	}

}
