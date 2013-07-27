<?php

require_once('MementoResource.php');

class MementoResourceExceptionTest extends PHPUnit_Framework_TestCase {

	public function testMementoResourceException() {

		$expectedStatusCode = 500;
		$expectedResponse = "hi, I'm a response object";
		$expectedOutput = "hi, I'm an outputPage object";
		$expectedConf = "hi, I'm a MementoConfig object";
		$expectedTextMessage = "really bad things happend";
		$expectedTitleMessage = "bad kimshee";

		try {
			throw new MementoResourceException(
				$expectedTextMessage, $expectedTitleMessage,
				$expectedOutput, $expectedResponse, $expectedStatusCode
			);
		} catch (MementoResourceException $e) {
			$this->assertEquals($expectedStatusCode, $e->getStatusCode());
			$this->assertEquals($expectedResponse, $e->getResponse());
			$this->assertEquals($expectedOutput, $e->getOutputPage());
			$this->assertEquals($expectedTextMessage, $e->getTextMessage());
			$this->assertEquals($expectedTitleMessage, $e->getTitleMessage());
		}

	}

}
