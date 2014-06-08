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
	'name' => 'Memento',
	'descriptionmsg' => 'extension-overview',
	'url' => 'https://www.mediawiki.org/wiki/Extension:Memento',
	'author' => array(
		'Harihar Shankar',
		'Herbert Van de Sompel',
		'Robert Sanderson',
		'Shawn M. Jones'
	),
	'version' => '2.0-RC2-SNAPSHOT'
);

// Set up the messages file
$wgExtensionMessagesFiles['Memento'] = ( __DIR__ ) . '/Memento.i18n.php';

// Set up the core classes used by Memento
$wgAutoloadClasses['MementoConfig'] = __DIR__ . '/MementoConfig.php';
$wgAutoloadClasses['MementoResource'] = __DIR__ . '/MementoResource.php';
$wgAutoloadClasses['MementoResourceException'] =
	__DIR__ . '/MementoResource.php';

// Set up the Memento (URI-M) Classes
$wgAutoloadClasses['MementoResourceDirectlyAccessed'] =
	__DIR__ . '/MementoResourceDirectlyAccessed.php';

// Set up the Original page (URI-R) Classes
$wgAutoloadClasses['OriginalResourceDirectlyAccessed'] =
	__DIR__ . '/OriginalResourceDirectlyAccessed.php';

// set up the Time Map (URI-T) classes
$wgAutoloadClasses['TimeMapResource'] = __DIR__ . '/TimeMapResource.php';
$wgAutoloadClasses['TimeMapFullResource'] =
	__DIR__ . '/TimeMapFullResource.php';
$wgAutoloadClasses['TimeMapPivotAscendingResource'] =
	__DIR__ . '/TimeMapPivotAscendingResource.php';
$wgAutoloadClasses['TimeMapPivotDescendingResource'] =
	__DIR__ . '/TimeMapPivotDescendingResource.php';
$wgAutoloadClasses['TimeMap'] = __DIR__ . '/TimeMap.php';
$wgSpecialPages['TimeMap'] = 'TimeMap';

// Set up the hooks for this class
$wgHooks['BeforePageDisplay'][] = 'Memento::onBeforePageDisplay';
$wgHooks['ArticleViewHeader'][] = 'Memento::onArticleViewHeader';
$wgHooks['BeforeParserFetchTemplateAndtitle'][] = 'Memento::onBeforeParserFetchTemplateAndtitle';
$wgHooks['ImageBeforeProduceHTML'][] = 'Memento::onImageBeforeProduceHTML';

// set up the Time Gate (URI-G) classes
$wgAutoloadClasses['MementoResourceFrom200TimeNegotiation'] =
	__DIR__ . '/MementoResourceFrom200TimeNegotiation.php';
$wgAutoloadClasses['TimeGateResourceFrom302TimeNegotiation'] =
	__DIR__ . '/TimegateResourceFrom302TimeNegotiation.php';
$wgAutoloadClasses['TimeNegotiator'] =
	__DIR__ . '/TimeNegotiator.php';
$wgAutoloadClasses['TimeGate'] = __DIR__ . '/TimeGate.php';
$wgSpecialPages['TimeGate'] = 'TimeGate';

/**
 * Main Memento class, used by hooks.
 *
 * This class handles the entry point from Mediawiki and performs
 * the mediation over the real work.  The goal is to separate
 * the Mediawiki setup code from the Memento code as much as possible
 * for clarity, testing, maintainability, etc.
 *
 */
class Memento {


	/**
	 * @var MementoResource $mementoResource: object that implements memento
	 */
	static private $mementoResource;

	/**
	 * @var string $articleDatetime: datetime of the article loaded
	 */
	static private $articleDatetime;

	/**
	 * @var boolean $oldIDSet: flag to indicate if this is an oldid page
	 */
	static private $oldIDSet;

	/**
	 * The ImageBeforeProduce HTML hook, used here to provide datetime
	 * negotiation for embedded images.
	 *
	 * @param $skin: Skin object for this page
	 * @param $title: Title object for this image
	 * @param $file: File object for this image
	 * @param $frameParams: frame parameters
	 * @param $handlerParams: handler parameters
	 * @param $time: not really used by hook
	 * @param $res: used to replace HTML for image rendering
	 *
	 * @return boolean indicating whether caller should use $res instead of 
	 * 		default HTML for image rendering
	 */
	public static function onImageBeforeProduceHTML(
		&$skin, &$title, &$file, &$frameParams, &$handlerParams, &$time, &$res) {

		$config = new MementoConfig();

		if ( $config->get('TimeNegotiationForThumbnails') == true ) {

			if ( self::$oldIDSet == true ) {
				$history = $file->getHistory($limit=1, $start=$articleDatetime);
				$file = $history[0];
			}

		}
	
		return true;
	}


	/**
	 * The BeforeParserFetchTemplateAndtitle hook, used here to change any
	 * Template pages loaded so that their revision is closer in date/time to
	 * that of the rest of the page.
	 *
	 * @param $parser: Parser object for this page
	 * @param $title: Title object for this page
	 * @param $skip: boolean flag allowing the caller to skip the rest of
	 *					statelessFetchTemplate
	 * @param $id: revision id of this page
	 *
	 * @return boolean indicating success to the caller
	 */
	public static function onBeforeParserFetchTemplateAndtitle(
		$parser, $title, &$skip, &$id ) {

		// $mementoResource is only set if we are on an actual page
		// as opposed to diff pages, edit pages, etc.
		if ( self::$mementoResource ) {
			self::$mementoResource->fixTemplate($title, $parser, $id);
		}

		return true;
	}

	/**
	 * The ArticleViewHeader hook, used to alter the headers before the rest
	 * of the data is loaded.
	 *
	 * Note: this is not called when the Edit, Diff or History pages are loaded.
	 *
	 * @param: $article: pointer to the Article Object from the hook
	 * @param: $outputDone: pointer to variable that indicates that 
	 *			the output should be terminated
	 * @param: $pcache: pointer to variable that indicates whether the parser
	 * 			cache should try retrieving the cached results
	 *
	 * @return boolean indicating success to the caller
	 */
	public static function onArticleViewHeader(
		&$article, &$outputDone, &$pcache
		) {

		// avoid processing Mementos for nonexistent pages
		// if we're an article, do memento processing, otherwise don't worry
		// if we're a diff page, Memento doesn't make sense
		if ( $article->getTitle()->isKnown() ) {

			self::$oldIDSet = ( $article->getOldID() != 0 );

			$revision = $article->getRevisionFetched();

			// avoid processing Mementos for bad revisions, 
			// let MediaWiki handle that case instead
			if ( is_object( $revision ) ) {

				$articleDatetime = $revision->getTimestamp();		
				$articleDatetime = '00000000000000';
	
				$config = new MementoConfig();
				$dbr = wfGetDB( DB_SLAVE );
				$oldID = $article->getOldID();
				$request = $article->getContext()->getRequest();
	
				self::$mementoResource =
					MementoResource::mementoPageResourceFactory(
						$config, $dbr, $article, $oldID, $request );
	
				try {
					self::$mementoResource->alterHeaders();
				} catch (MementoResourceException $e) {
	
					$out = $article->getContext()->getOutput();
	
					// unset for future hooks in the chain
					self::$mementoResource = null;
	
					MementoResource::renderError(
						$out, $e, $config->get('ErrorPageType') );
				}
			}
		}

		return true; // TODO: return false if exception thrown?
	}

	/**
	 * The main hook for the plugin.
	 *
	 * @param $out: pointer to the OutputPage Object from the hook
	 * @param $skin: skin object that will be used to generate the page
	 *
	 * @returns boolean indicating success to the caller
	 */
	public static function onBeforePageDisplay(&$out, &$skin) {

		$status = true;

		// if we didn't get declared during ArticleViewHeader, then there is
		// no need to run the additional Memento code
		if ( self::$mementoResource ) {

			try {
				self::$mementoResource->alterEntity();
			} catch (MementoResourceException $e) {
				$config = new MementoConfig();

				// unset for future hooks in the chain
				self::$mementoResource = null;

				MementoResource::renderError(
					$out, $e, $config->get('ErrorPageType') );
			}

		}

		return $status; // TODO: return false if exception thrown?
	}
}
