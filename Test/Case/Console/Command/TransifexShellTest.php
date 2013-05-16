<?php

App::uses('TransifexShell', 'Transifex.Console/Command');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class TransifexShellTest extends MyCakeTestCase {

	public $TransifexShell;

	public function setUp() {
		parent::setUp();

		$this->TransifexShell = new TransifexShell();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->TransifexShell));
		$this->assertInstanceOf('TransifexShell', $this->TransifexShell);
	}

	//TODO
}
