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

# ensure that the script can't be executed outside of Mediawiki
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

$timegate_desc = <<<EOT
The Memento TimeGate performs content negotiation in datetime dimension. 
It understands accept-datetime HTTP header and redirects the client to the 
appropriate revision of an article that was alive at that specified datetime.
EOT;

$timegate_welcome = <<<EOT
This is a Memento Timegate for your wiki. To see Memento in action, either 
follow the instructions from the 
[http://www.mediawiki.org/wiki/Extension:Memento MediaWiki Extension] page or 
type in the address of the wiki page in this format: 
	http://yourwikisite/wiki/index.php/Special:TimeGate/http://yourwikisite/wiki/index.php/Main_Page 
where, the address that follows the TimeGate URL is the address of the article.
EOT;

$timegate_404_namespace_title = <<<EOT
Error 404: Either the resource does not exist or the namespace is not 
understood by memento for the title: '$1'.
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

$messages['en'] = array(
	'memento' => 'Memento',
	'extension-overview' => $overview,
	'timegate' => 'Memento TimeGate',
	'timegate-desc' => $timegate_desc,
	'timegate-welcome-message' => $timegate_welcome,
	'timegate-404-namespace-title' => $timegate_404_namespace_title,
	'timegate-404-title' => "Error 404: Resource does not exist for the title: '$1'.",
	'timegate-404-inaccessible' => "Error 404: Resource '$1' is not accessible.",
	'timegate-400-date' => $timegate_400_date,
	'timemap' => 'Memento TimeMap',
	'timemap-desc' => $timemap_desc,
	'timemap-404-namespace-title' => $timemap_404_namespace_title,
	'timemap-404-title' => "Error 404: Resource does not exist for the title: '$1'.",
	'timemap-404-inaccessible' => "Error 404: Resource '$1' is not accessible.",
	'timemap-400-date' => "Error 400: Requested date $1 not parseable.<br/>",
	'timegate-405-badmethod' => "Unsupported method used for page operation.<br />",
);
