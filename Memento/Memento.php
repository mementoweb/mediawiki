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

// Set up the extension
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Memento',
	'descriptionmsg' => 'memento-desc',
	'url' => 'https://www.mediawiki.org/wiki/Extension:Memento',
	'author' => array(
		'Harihar Shankar',
		'Herbert Van de Sompel',
		'Robert Sanderson',
		'Shawn M. Jones'
	),
	'version' => '2.1.4'
);

// Set up the messages file
$wgMessagesDirs['MementoHeaders'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['Memento'] = __DIR__ . '/Memento.i18n.php';
$wgExtensionMessagesFiles['MementoAlias'] = __DIR__ . '/Memento.alias.php';

// Set up the core classes used by Memento
$wgAutoloadClasses['Memento'] = __DIR__ . '/Memento.body.php';
$wgAutoloadClasses['MementoResource'] = __DIR__ . '/MementoResource.php';

// Set up the Memento (URI-M) Classes
$wgAutoloadClasses['MementoResourceDirectlyAccessed'] =
	__DIR__ . '/MementoResourceDirectlyAccessed.php';

// Set up the Original page (URI-R) Classes
$wgAutoloadClasses['OriginalResourceDirectlyAccessed'] =
	__DIR__ . '/OriginalResourceDirectlyAccessed.php';

// set up the Time Map (URI-T) classes
$wgAutoloadClasses['TimeMapResource'] = __DIR__ . '/TimeMapResource.php';
$wgAutoloadClasses['TimeMapFullResource'] = __DIR__ . '/TimeMapFullResource.php';
$wgAutoloadClasses['TimeMapPivotAscendingResource'] =
	__DIR__ . '/TimeMapPivotAscendingResource.php';
$wgAutoloadClasses['TimeMapPivotDescendingResource'] =
	__DIR__ . '/TimeMapPivotDescendingResource.php';
$wgAutoloadClasses['TimeMap'] = __DIR__ . '/TimeMap.php';
$wgSpecialPages['TimeMap'] = 'TimeMap';


// set up the Time Gate (URI-G) classes
$wgAutoloadClasses['TimeGateResourceFrom302TimeNegotiation'] =
	__DIR__ . '/TimegateResourceFrom302TimeNegotiation.php';
$wgAutoloadClasses['TimeNegotiator'] = __DIR__ . '/TimeNegotiator.php';
$wgAutoloadClasses['TimeGate'] = __DIR__ . '/TimeGate.php';
$wgSpecialPages['TimeGate'] = 'TimeGate';

// default settings values
$wgMementoIncludeNamespaces = array( 0 );
$wgMementoTimemapNumberOfMementos = 500;
$wgMementoTimeNegotiationForThumbnails = false;

// instantiate entry point
$wgMemento = new Memento();

// Set up the hooks for this class
$wgHooks['ArticleViewHeader'][] = $wgMemento;
$wgHooks['BeforeParserFetchTemplateAndtitle'][] = $wgMemento;
$wgHooks['ImageBeforeProduceHTML'][] = $wgMemento;
