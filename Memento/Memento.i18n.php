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
 * Internationalisation file for extension Memento.
 *
 * @addtogroup Extensions
 */

$messages = array();

$overview = <<<EOT
The Memento extension implements support for the Accept-Datetime HTTP header to
perform content negotiation in the date-time dimension. It will retrieve the 
revision of a given article by matching a specified datetime. Please visit 
http://mementoweb.org for more information.  
EOT;

$timemapWelcome = <<<EOT
This Special Page takes care of creating Memento TimeMaps for your wiki, which
are machine-readable versions of the history of the pages they represent.

For a specific page, it lists the Mementos archived for that page.

To see Memento in action, either follow instructions from the 
[http://www.mediawiki.org/wiki/Extension:Memento MediaWiki Extension] page or 
type in the address of the wiki page in this format:
	http://yourwikisite/wiki/index.php/Special:TimeMap/YourPage
where, the name that follows the TimeMap URL is the title of the article.
EOT;

$timemapDesc = <<<EOT
The Memento TimeMap retrieves the revision list of an article including the 
datetime when the revision was created. The revision list is serialized as 
application/link-format. Please see http://mementoweb.org for more information.
EOT;

$status404NamespaceTitle = <<<EOT
Either the resource does not exist or the namespace is not 
understood by memento for the title: '$1'.
EOT;

$status403Inaccessible = "Error 403: Resource '$1' is not accessible.";

$timegateWelcome = <<<EOT
This Special Page takes care of performing datetime negotiation for your wiki, 
which is key to the Memento process.

For a specific page, it performs datetime negotiation for the given page, 
redirecting you to the closest page to the time requested by your browser.

To see Memento in action, either follow instructions from the 
[http://www.mediawiki.org/wiki/Extension:Memento MediaWiki Extension] page or 
type in the address of the wiki page in this format:
	http://yourwikisite/wiki/index.php/Special:TimeGate/YourPage
where, YourPage that follows the TimeGate URL is the title of our article.
EOT;

$timegate400Date = <<<EOT
Error 400: Requested date '$1' not parseable.<br />
<b>First Memento:</b> $2<br />
<b>Last Memento:</b> $3<br />
EOT;

$timemap400Date = <<<EOT
Requested pivot date '$1' not parseable.<br />
EOT;

$messages['en'] = array(
	'memento' => 'Memento',
	'extension-overview' => $overview,
	'timegate-title' => 'Memento TimeGate',
	'timegate-400-date' => $timegate400Date,
	'timemap' => 'Memento TimeMap',
	'timemap-title' => 'Memento TimeMap',
	'timemap-welcome-message' => $timemapWelcome,
	'timemap-specialpage-listing' => 'Memento',
	'timemap-desc' => $timemapDesc,
	'timemap-404-title' => $status404NamespaceTitle,
	'timemap-403-inaccessible' => $status403Inaccessible,
	'timemap-400-date' => $timemap400Date,
	'timegate' => 'Memento TimeGate',
	'timegate-welcome-message' => $timegateWelcome,
	'timegate-404-title' => $status404NamespaceTitle,
	'timegate-403-inaccessible' => $status403Inaccessible,
);
