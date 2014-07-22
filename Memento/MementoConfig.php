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
 * MementoConfig
 *
 * This class provides a single place for all of Memento's configuration,
 * eliminating the need to have global settings variables everywhere, and
 * providing read-only access to settings.
 *
 * This class is also where defaults are set or stored.
 *
 */
class MementoConfig {

	/**
	 * Private associative array holding the key/value pairs of each setting.
	 */
	private $settings = array();

	/**
	 * Constructor which is necessary, but takes no arguments as it
	 * loads all settings from the global namespace.
	 */
	function __construct() {

		global $wgMementoTimemapNumberOfMementos;
		global $wgMementoErrorPageType;
		global $wgMementoTimeNegotiation;
		global $wgMementoRecommendedRelations;
		global $wgMementoExcludeNamespaces;
		global $wgMementoTimeNegotiationForThumbnails;

		$this->settings['NumberOfMementos'] =
			$this->setDefault(
				$wgMementoTimemapNumberOfMementos, 500 );

		$this->settings['ErrorPageType'] =
			$this->setDefault(
				$wgMementoErrorPageType, 'friendly' );

		$this->settings['RecommendedRelations'] =
			$this->setDefault(
				$wgMementoRecommendedRelations, false );

		$excludeNamespaceDefault = array_diff(
			MWNamespace::getValidNamespaces(),
			MWNamespace::getContentNamespaces()
		);

		$this->settings['ExcludeNamespaces'] =
			$this->setDefault(
				$wgMementoExcludeNamespaces, $excludeNamespaceDefault
				);

		$this->settings['TimeNegotiationForThumbnails'] =
			$this->setDefault(
				$wgMementoTimeNegotiationForThumbnails, false );
	}

	/**
	 * Set return the value given or, if it is not set, the default.
	 *
	 * @param $valueToCheck: string
	 * @param $defaultValue: anything
	 * @return mixed
	 */
	public function setDefault( $valueToCheck, $defaultValue ) {

		$value = null;

		if ( isset( $valueToCheck ) ) {
			$value = $valueToCheck;
		} else {
			$value = $defaultValue;
		}

		return $value;
	}

	/**
	 * Simple get function that retrieves settings.
	 */
	public function get( $setting ) {
		return $this->settings[$setting];
	}
}
