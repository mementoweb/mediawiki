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

/**
 * This class is the exception used by all MementoResource types.
 *
 * The large number of getters exist mainly to conform to the standard
 * set by the PHP built-in exception class.
 *
 */
class MementoResourceException extends MWException {

	/**
	 * @var string $statusCode - intended HTTP status code
	 */
	private $statusCode;

	/**
	 * @var Response object $response - response object from throwing code
	 */
	private $response;

	/**
	 * @var OutputPage object $output - OutputPage object from throwing code
	 */
	private $outputPage;

	/**
	 * @var string $textMessage - the full text to display to the user
	 */
	private $textMessage;

	/**
	 * @var string $titleMessage - the title text to display to the user
	 */
	private $titleMessage;

	/**
	 * redefined constructor for our purposes
	 *
	 * @param $textMessage - message key (string) for page text
	 * @param $titleMessage - message key (string) for page title
	 * @param $outputPage - OutputPage object from this session
	 * @param $response	- response object from this session
	 * @param $statusCode - the HTTP status code to use in the response
	 * @param $params - parameters for the $textMessage
	 *
	 */
	public function __construct(
		$textMessage, $titleMessage, $outputPage, $response, $statusCode,
		$params = array()) {

		$this->statusCode = $statusCode;
		$this->response = $response;
		$this->outputPage = $outputPage;
		$this->textMessage = $textMessage;
		$this->titleMessage = $titleMessage;
		$this->params = $params;

		parent::__construct($textMessage, $statusCode, null);
	}

	/**
	 * custom string representation of object (for testing)
	 */
	public function __toString() {
		return __CLASS__ . ":[{$this->statusCode}]: {$this->textMessage}\n";
	}

	/**
	 * getter for StatusCode
	 *
	 * @return $statusCode
	 */
	public function getStatusCode() {
		return $this->statusCode;
	}

	/**
	 * getter for response object
	 *
	 * @return Response Object for this session
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * getter for outputPage
	 *
	 * @return OutputPage Object for this session
	 */
	public function getOutputPage() {
		return $this->outputPage;
	}

	/**
	 * getter for textMessage
	 *
	 * @return message key (string) for page text
	 */
	public function getTextMessage() {
		return $this->textMessage;
	}

	/**
	 * getter for titleMessage
	 *
	 * @return message key (string) for page title
	 */
	public function getTitleMessage() {
		return $this->titleMessage;
	}

	/**
	 * getter for params
	 *
	 * @return message parameters for page text message
	 */
	public function getParams() {
		return $this->params;
	}

}
