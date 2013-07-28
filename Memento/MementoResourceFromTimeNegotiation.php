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

class MementoResourceFromTimeNegotiation extends OriginalResource {

	/**
	 * Render the page
	 *
	 * 1.  get the ID for the page you want, based on Accept-Datetime
	 * 2.  replace the existing page with the contents of that page
	 * 3.  ensure that the Content-Location header contains the memento URI
	 *
	 */
	public function render() {

		$requestDatetime = $this->out->getRequest()->getHeader(
			'ACCEPT-DATETIME');

		$mwMementoTimestamp = $this->parseRequestDateTime( $requestDatetime );

		$pageID = $this->title->getArticleID();

		$first = $this->getFirstMemento( $this->dbr, $pageID );

		$last = $this->getLastMemento( $this->dbr, $pageID );

		$mwMementoTimestamp = $this->chooseBestTimestamp(
			$first['timestamp'], $last['timestamp'], $mwMementoTimestamp );

		$memento = $this->getCurrentMemento(
				$this->dbr, $pageID, $mwMementoTimestamp );

		$id = $memento['id'];

		// so that they get a warning if they try to edit the page
		$this->out->setRevisionId($memento['id']);

		$oldArticle = new Article( $title = $this->title, $oldid = $id );
		$oldArticleContent = $oldArticle->getRevisionFetched()->getContent();

		$mementoArticleText = $oldArticleContent->getWikitextForTransclusion();

		$title = $this->title->getPartialURL();

		$url = $this->getFullURIForID( $this->mwrelurl, $id, $title );


		$this->out->clearHTML();
		$this->out->addWikiText($mementoArticleText);

		# the following headers comply with Pattern 1.2 of the Memento RFC
		$this->out->getRequest()->response()->header(
			"Content-Location: $url", true );

		$this->out->getRequest()->response()->header(
			"Vary: accept-datetime", true );

		$linkValues = '<' . $this->out->getRequest()->getFullRequestURL() . 
			'>; rel="original timegate",';

		$linkValues .= $this->constructMementoLinkHeaderEntry(
			$this->mwrelurl, $title, $first['id'],
			$first['timestamp'], 'memento first' ) . ',';
			
		$linkValues .= $this->constructMementoLinkHeaderEntry(
			$this->mwrelurl, $title, $last['id'],
			$last['timestamp'], 'memento last' );

		$this->out->getRequest()->response()->header(
			"Link: $linkValues", true );
	}
}
