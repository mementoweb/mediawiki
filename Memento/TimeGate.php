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
	 * @param: $urlparam: string - the title parameter that Mediawiki returns
	 * 		which turns out to be the part of the url after Special:TimeGate
	 *
	 */
	function execute( $urlparam ) {

		$config = new MementoConfig();
		$out = $this->getOutput();

		if ( $config->get('Negotiation') == "200" ) {
			$out->showErrorPage( 'nosuchspecialpage', 'nosuchspecialpagetext' );
		} else {
	
			$this->setHeaders();
	
			if ( !$urlparam ) {
				$out->addHTML( wfMessage( 'timegate-welcome-message' )->parse() );
				return;
			} else {
	
				$dbr = wfGetDB( DB_SLAVE );
	
				$server = $config->get('Server');
				$waddress = str_replace( '$1', '', $config->get('ArticlePath') );
				$title = Title::newFromText( $urlparam );
	
				try {
					if ( in_array( $title->getNamespace(), 
						$config->get('ExcludeNamespaces') ) ) {
						$titleMessage = 'timegate';
						$textMessage = 'timegate-403-inaccessible';
						$response = $this->getOutput()->getRequest()->response();

						throw new MementoResourceException(
							$textMessage, $titleMessage,
							$out, $response, 403, array( $urlparam )
						);
					}

					$page = new TimeGateResource(
						$out, $config, $dbr, $title, $urlparam, null );
					$page->render();
				} catch (MementoResourceException $e) {
					MementoResource::renderError(
						$out, $e, $config->get('ErrorPageType') );
				}
	
			}
		}

	}

}
