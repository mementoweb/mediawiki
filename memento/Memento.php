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

// Set up the extension
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Special:Memento',
	'descriptionmsg' => 'extension-overview',
	'url' => 'https://www.mediawiki.org/wiki/Extension:Memento',
	'author' => array(
		'Harihar Shankar',
		'Herbert Van de Sompel',
		'Robert Sanderson',
		'Shawn M. Jones'
		),
	'version' => 'development'
);

//require_once( ( __DIR__ ) . '/MementoUtilities.php');

// TODO:  have the make file change 'version' above to an actual
// version upon release

// Set up the messages file
$wgExtensionMessagesFiles['Memento'] = ( __DIR__ ) . '/Memento.i18n.php';

// Load the classes into MediaWiki
$wgAutoloadClasses['MementoConfig'] = __DIR__ . '/MementoConfig.php';
$wgAutoloadClasses['MementoFactory'] = __DIR__ . '/MementoFactory.php';
$wgAutoloadClasses['MementoResource'] = __DIR__ . '/MementoResource.php';
$wgAutoloadClasses['MementoResourceException'] =
	__DIR__ . '/MementoResource.php';
$wgAutoloadClasses['OriginalResource'] = __DIR__ . '/OriginalResource.php';
$wgAutoloadClasses['OriginalWithTimeNegotiationResource'] =
	__DIR__ . '/OriginalWithTimeNegotiationResource.php';
$wgAutoloadClasses['OriginalWithMementoHeadersOnlyResource'] =
	__DIR__ . '/OriginalWithMementoHeadersOnlyResource.php';
$wgAutoloadClasses['MementoWithHeaderModificationsResource'] =
	__DIR__ . '/MementoWithHeaderModificationsResource.php';
$wgAutoloadClasses['TimeGateResource'] = __DIR__ . '/TimeGateResource.php';
$wgAutoloadClasses['TimeMapResource'] = __DIR__ . '/TimeMapResource.php';
$wgAutoloadClasses['TimeMapFullResource'] =
	__DIR__ . '/TimeMapFullResource.php';
$wgAutoloadClasses['TimeMapPivotAscendingResource'] =
	__DIR__ . '/TimeMapPivotAscendingResource.php';
$wgAutoloadClasses['TimeMapPivotDescendingResource'] =
	__DIR__ . '/TimeMapPivotDescendingResource.php';
$wgAutoloadClasses['TimeGate'] = __DIR__ . '/TimeGate.php';
$wgAutoloadClasses['TimeMap'] = __DIR__ . '/TimeMap.php';

// Set up the special pages
$wgSpecialPages['TimeGate'] = 'TimeGate';
$wgSpecialPages['TimeMap'] = 'TimeMap';

// Set up the hooks
$wgHooks['BeforePageDisplay'][] = 'Memento::mediator';
$wgHooks['ArticleViewHeader'][] = 'Memento::articleViewHeader';

/**
 * Main Memento class, used by hooks.
 *
 * This class handles the entry point from Mediawiki and performs
 * the mediation over the real work.  The goal is to separate
 * the Mediawiki setup code from the Memento code as much as possible
 * for clarity, testing, maintainability, etc.
 *
 * Author's note:  the only reason I'm using this static class is to allow
 * for the passing of $oldID to the main hook.
 *
 */
class Memento {

	/**
	 * @var string $article: access to the Article Object for this page 
	 */
	static private $article;

	/**
	 * The ArticleViewHeader hook, used to feed values into lodal memeber
	 * variables, to minimize the use of globals.
	 *
	 * Note: this is not called when the Edit or History pages are loaded.
	 *
	 * @param: $article: pointer to the Article Object from the hook
	 * @param: $outputDone: pointer to variable that indicates that 
	 *			the output should be terminated
	 * @param: $pcache: pointer to variable that indicates whether the parser
	 * 			cache should try retrieving the cached results
	 */
	public static function articleViewHeader(
		&$article, &$outputDone, &$pcache
		) {

		$status = true;

//		self::$oldID = $article->getOldID();
		self::$article = $article;

		return $status;
	}

	/**
	 * The main hook for the plugin.
	 *
	 * @param $out: pointer to the OutputPage Object from the hook
	 * @param $skin: skin object that will be used to generate the page
	 *
	 * @returns boolean indicating success to the caller
	 */
	public static function mediator(&$out, &$skin) {

		$status = true;

		// if we're an article, do memento processing, otherwise don't worry
		if ( $out->isArticle() ) {
			$config = new MementoConfig();
			$dbr = wfGetDB( DB_SLAVE );
			$oldID = self::$article->getOldID();
			$title = self::$article->getTitle();

			try {
				$page = MementoResource::MementoPageResourceFactory(
					$out, $config, $dbr, $oldID, $title );
				$page->render();
			} catch (MementoResourceException $e) {
				MementoResource::renderError(
					$out, $e, $config->get('ErrorPageType') );
			}

		}

		return $status;
	}
}
