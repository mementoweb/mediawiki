<?php
/**
 * This file is part of the Memento Extension to MediaWiki
 * https://www.mediawiki.org/wiki/Extension:Memento
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
 * Main Memento class, used by hooks.
 *
 * This class handles the entry point from Mediawiki and performs
 * the mediation over the real work.  The goal is to separate
 * the Mediawiki setup code from the Memento code as much as possible
 * for clarity, testing, maintainability, etc.
 *
 */
class Memento {

#	/**
#	 * @var MementoResource $mementoResource object that implements memento
#	 */
#	private $mementoResource;
#
#	/**
#	 * @var string $articleDatetime datetime of the article loaded
#	 */
#	private $articleDatetime;
#
#	/**
#	 * @var bool $oldIDSet flag to indicate if this is an oldid page
#	 */
#	private $oldIDSet;
#
#	/**
#	 * The ImageBeforeProduce HTML hook, used here to provide datetime
#	 * negotiation for embedded images.
#	 *
#	 * @param Skin &$skin Skin object for this page
#	 * @param Title &$title Title object for this image
#	 * @param File &$file File object for this image
#	 * @param array &$frameParams frame parameters
#	 * @param array &$handlerParams handler parameters
#	 * @param string &$time not really used by hook
#	 * @param string &$res used to replace HTML for image rendering
#	 *
#	 * @return bool indicating whether caller should use $res instead of
#	 * 		default HTML for image rendering
#	 */
#	public function onImageBeforeProduceHTML(
#		&$skin, &$title, &$file, &$frameParams, &$handlerParams, &$time, &$res ) {
#		global $wgMementoTimeNegotiationForThumbnails;
#
#		if ( $wgMementoTimeNegotiationForThumbnails === true ) {
#			if ( $file != false ) {
#				if ( $this->oldIDSet === true ) {
#					$history = $file->getHistory(
#						/* $limit = */ 1,
#						/* $start = */
#						$this->articleDatetime );
#					$file = $history[0];
#				}
#			}
#
#		}
#
#		return true;
#	}
#
	/**
	 * The BeforeParserFetchTemplateAndtitle hook, used here to change any
	 * Template pages loaded so that their revision is closer in date/time to
	 * that of the rest of the page.
	 *
	 * @param Parser $parser Parser object for this page
	 * @param Title $title Title object for this page
	 * @param bool &$skip boolean flag allowing the caller to skip the rest of statelessFetchTemplate
	 * @param int &$id revision id of this page
	 *
	 * @return bool indicating success to the caller
	 */
	public function onBeforeParserFetchTemplateAndtitle(
		$parser, $title, &$skip, &$id ) {

#		// $mementoResource is only set if we are on an actual page
#		// as opposed to diff pages, edit pages, etc.
#		if ( $this->mementoResource ) {
#			$this->mementoResource->fixTemplate( $title, $parser, $id );
#		}
#
#		return true;

		if ($title->isKnown()) {

			$article = new Article($title);

			$oldID = $article->getOldID();
			$revision = $article->getRevisionFetched();

			if  (is_object( $revision ) ) {
			
				$db = wfGetDB( DB_REPLICA );
				
				$mementoResource = MementoResource::mementoPageResourceFactory( $db, $article, $oldID );
				$mementoResource->alterHeaders();
			}


		}

	}

	/**
	 * The ArticleViewHeader hook, used to alter the headers before the rest
	 * of the data is loaded.
	 *
	 * Note: this is not called when the Edit, Diff or History pages are loaded.
	 *
	 * @param Article &$article pointer to the Article Object from the hook
	 * @param bool &$outputDone pointer to variable that indicates that
	 *                         the output should be terminated
	 * @param bool &$pcache pointer to variable that indicates whether the parser
	 * 			cache should try retrieving the cached results
	 *
	 * @return bool indicating success to the caller
	 */
	public static function onArticleViewHeader(
		&$article, &$outputDone, &$pcache
		) {
		// avoid processing Mementos for nonexistent pages
		// if we're an article, do memento processing, otherwise don't worry
		// if we're a diff page, Memento doesn't make sense
		if ( $article->getTitle()->isKnown() ) {
#			$this->oldIDSet = ( $article->getOldID() != 0 );

			$revision = $article->getRevisionFetched();

			// avoid processing Mementos for bad revisions,
			// let MediaWiki handle that case instead
			if ( is_object( $revision ) ) {
#				$this->articleDatetime = $revision->getTimestamp();

				$db = wfGetDB( DB_REPLICA );
				$oldID = $article->getOldID();
				$request = $article->getContext()->getRequest();

#				$this->mementoResource = MementoResource::mementoPageResourceFactory( $db, $article, $oldID );
				$mementoResource = MementoResource::mementoPageResourceFactory( $db, $article, $oldID );

#				$this->mementoResource->alterHeaders();
				$mementoResource->alterHeaders();
			}
		}

		return true;
	}

}
