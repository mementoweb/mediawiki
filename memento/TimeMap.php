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
		parent::__construct( "TimeMap" );
	}

	/**
	 * The init function that is called by mediawiki when loading this
	 * SpecialPage.
	 *
	 * @param $urlpar: string; the title parameter returned by Mediawiki
	 *				which, in this case, is the URI for which we want TimeMaps
	 */
	function execute($urlparam) {

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
			$title = str_replace( $server . $waddress, "", $urlparam );

			$title = Title::newFromText( $title );

			$page = new TimeMapPage(
				$out, $config, $dbr, $urlparam, $title);

			try {
				$page->render();
			} catch (MementoResourceException $e) {
				$page->renderError($e);
			}

		}

	}

}

?>
