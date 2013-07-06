<?php

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

		global $wgArticlePath;
		global $wgServer;
		global $wgMementoExcludeNamespaces;
		global $wgMementoTimeMapNumberOfMementos;
		global $wgMementoErrorPageType;
		global $wgScriptPath; // for ArticlePath default

		$this->settings['ArticlePath'] = 
			$this->setDefault(
				$wgArticlePath, "$wgScriptPath/index.php/$1" );

		// TODO: what if this isn't set by the Mediawiki installation?
		// the documentation at http://www.mediawiki.org/wiki/Manual:$wgServer
		// says it will be detected at run time using 
		// WebRequest::detectServer()
		$this->settings['Server'] = $wgServer;

		// this can be null
		$this->settings['ExcludedNamespaces'] = $wgMementoExcludeNamespaces;

		$this->settings['NumberOfMementos'] = 
			$this->setDefault(
				$wgMementoTimeMapNumberOfMementos, 500 );

		$this->settings['ErrorPageType'] = 
			$this->setDefault(
				$wgMementoErrorPageType, "traditional" );

	}

	/**
	 * Set return the value given or, if it is not set, the default.
	 *
	 * @param $settingToCheck: string
	 * @param $defaultValue: anything
	 *
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

?>
