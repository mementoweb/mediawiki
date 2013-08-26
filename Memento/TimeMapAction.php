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
class TimeMapAction extends Action {

	public function getName() {
		return 'timemap';
	}

	public function execute() {
		return true;
	}

	public function show() {

		$status = true;

		$out = $this->getOutput();
		$this->setHeaders();

		$config = new MementoConfig();
		$dbr = wfGetDB( DB_SLAVE );

		$server = $config->get('Server');
		$title = $this->getTitle();
		$urlparam = $title->getFullURL();

		try {
			$page = new TimeMapFullResource(
				$out, $config, $dbr, $title, $urlparam, null );
			$page->render();
			print_r( $out->getRequest()->getQueryValues() );
			echo "done?";
			$status = true;
		} catch (MementoResourceException $e) {
			MementoResource::renderError(
				$out, $e, $config->get('ErrorPageType') );
			$status = false;
		}

		return $status;

	}

}
