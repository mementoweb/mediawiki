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

$timemap_welcome = <<<EOT
This Special Page takes care of creating Memento TimeMaps for your wiki, which are machine-readable versions of the history of the pages they represent.

For a specific page, it lists the Mementos archived for that page.

To see Memento in action, either follow instructions from the 
[http://www.mediawiki.org/wiki/Extension:Memento MediaWiki Extension] page or 
type in the address of the wiki page in this format:
	http://yourwikisite/wiki/index.php/Special:TimeMap/YourPage
where, the address that follows the TimeMap URL is the address of the article.
EOT;

$timemap_desc = <<<EOT
The Memento TimeMap retrieves the revision list of an article including the 
datetime when the revision was created. The revision list is serialized as 
application/link-format. Please see http://mementoweb.org for more information.
EOT;

$timemap_404_namespace_title = <<<EOT
Error 404: Either the resource does not exist or the namespace is not 
understood by memento for the title: '$1'.
EOT;

$timegate_400_date = <<<EOT
Error 400: Requested date '$1' not parseable.<br />
<b>First Memento:</b> $2<br />
<b>Last Memento:</b> $3<br />
EOT;

$timemap_400_date = <<<EOT
Error 400: Requested pivot date '$1' not parseable.<br />
EOT;

$messages['en'] = array(
	'memento' => 'Memento',
	'extension-overview' => $overview,
	'timegate-title' => 'Memento TimeGate',
	'timegate-400-date' => $timegate_400_date,
	'timemap' => 'Memento',
	'timemap-title' => 'Memento TimeMap',
	'timemap-welcome-message' => $timemap_welcome,
	'timemap-specialpage-listing' => 'Memento',
	'timemap-desc' => $timemap_desc,
	'timemap-404-title' => "Error 404: Resource does not exist for the title: '$1'.",
	'timemap-403-inaccessible' => "Error 403: Resource '$1' is not accessible.",
	'timemap-400-date' => $timemap_400_date,
);
