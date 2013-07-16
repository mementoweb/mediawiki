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
 * @see http://www.mediawiki.org/wiki/Security_for_developers
 */
if ( ! defined( 'MEDIAWIKI' ) ) {
	echo "Not a valid entry point";
	exit( 1 );
}

/**
 * Static Factory Class used by the other entry points.
 *
 * Note to future maintainers:
 * This class should only consist of the single factory method to allow
 * separation of code into areas of responsibility.
 *
 * @see http://kore-nordmann.de/blog/0103_static_considered_harmful.html
 *
 * Factory methods are one of the cases in the above article considered
 * "safe" for static class use in PHP.
 *
 */
class MementoFactory {

	/**
	 * Factory Method used to generate the correct MementoPage class.
	 * 
	 * @param $out: OutputPage object, used in object instantiation
	 * @param $caller: string, indicates which class called the factory
	 * @param $conf: MementoConfig object, used in object instantiation
	 *
	 */
	public static function PageFactory( $out, $caller, $conf, $dbr ) {

		$pageClass = null;

		// TODO: make decision about which MementoPage to load (200 vs. 302) based
		// on $conf->get('$wgMementoPattern')

		if ( $caller == "Original" ) {
			$pageClass = new OriginalPage( $out, $conf, $dbr );
		} elseif ( $caller == "Memento" ) {
			$pageClass = new MementoPage( $out, $conf, $dbr );
		} elseif ( $caller == "TimeGate" ) {
			$pageClass = new TimeGatePage( $out, $conf, $dbr );
		} elseif ( $caller == "TimeMap" ) {
			$pageClass = new TimeMapPage( $out, $conf, $dbr );
		} else {
			$pageClass = "NOT IMPLEMENTED";
		}

		return $pageClass;
	}

}
