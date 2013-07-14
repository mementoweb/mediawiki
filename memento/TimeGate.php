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
 * Special Page Implementation of a Memento TimeGate
 * @see http://mementoweb.org
 *
 * This class handles the entry point from Mediawiki and performs
 * the mediation over the real work.  The goal is to separate
 * the Mediawiki setup code from the Memento code as much as possible
 * for clarity, testing, maintainability, etc.
 *
 */
class TimeGate extends SpecialPage {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct( "TimeGate" );
	}

	/**
	 * The init function that is called by Mediawiki when loading this
	 * SpecialPage.
	 *
	 * @param: $par: string - the title parameter that Mediawiki returns
	 * 		which turns out to be the part of the url after Special:TimeGate
	 *
	 */
	 function execute( $par ) {

		$out = $this->getOutput();
		$this->setHeaders();

		if ( !$par ) {
			$out->addHTML( wfMessage( 'timegate-welcome-message' )->parse() );
			return;
		} else {

			$textMessage = 'not implemented yet';
			$titleMessage = 'title stuff';
			$outputPage = $out;
			$response = $out->getRequest()->response();
			$statusCode = 200;

			throw new MementoResourceException(
				$textMessage, $titleMessage, 
				$outputPage, $response, $statusCode);

			$config = new MementoConfig();
			$dbr = wfGetDB( DB_SLAVE );
	
			$page = MementoFactory::PageFactory(
				$out, "TimeGate", $config, $dbr
				);
			$page->render();
	
			echo "TimeGate is running<br />";
		}

	 }

}

?>
