<?php
/**
 * Internationalisation file for extension Memento.
 *
 * @addtogroup Extensions
 */

$messages = array();

$messages['en'] = array(
	'memento' => 'Memento',
	'extension-overview' => 'The Memento extension implements support for the Accept-Datetime HTTP header to perform content negotiation in the date-time dimension. It will retrieve the revision of a given article by matching a specified datetime. Please visit http://mementoweb.org for more information.',
	'timegate' => 'Memento TimeGate',
	'timegate-desc' => 'The Memento TimeGate performs content negotiation in datetime dimension. It understands accept-datetime HTTP header and redirects the client to the appropriate revision of an article that was alive at that specified datetime.',
	'timegate-welcome-message' => 'This is a Memento Timegate for your wiki. To see Memento in action, either follow the instructions from the [http://www.mediawiki.org/wiki/Extension:Memento MediaWiki Extension] page or type in the address of the wiki page in this format: 
	http://yourwikisite/wiki/index.php/Special:TimeGate/http://yourwikisite/wiki/index.php/Main_Page 
where, the address that follows the TimeGate URL is the address of the article.',
	'timegate-404-namespace-title' => "Error 404: Either the resource does not exist or the namespace is not understood by memento for the title: '$1'.",
	'timegate-404-title' => "Error 404: Resource does not exist for the title: '$1'.",
	'timegate-404-inaccessible' => "Error 404: Resource '$1' is not accessible.",
	'timegate-400-date' => "Error 400: Requested date $1 not parseable.<br/>",
	'timegate-400-first-memento' => "<b>First Memento:</b> <a href=$1>$1</a><br/>",
	'timegate-400-last-memento' => "<b>Last Memento:</b> <a href=$1>$1</a><br/>",
	'timemap' => 'Memento TimeMap',
	'timemap-desc' => 'The Memento TimeMap retrieves the revision list of an article including the datetime when the revision was created. The revision list is serialized as application/link-format. Please see http://mementoweb.org for more information.',
	'timemap-404-namespace-title' => "Error 404: Either the resource does not exist or the namespace is not understood by memento for the title: '$1'.",
	'timemap-404-title' => "Error 404: Resource does not exist for the title: '$1'.",
	'timemap-404-inaccessible' => "Error 404: Resource '$1' is not accessible.",
	'timemap-400-date' => "Error 400: Requested date $1 not parseable.<br/>",
);
