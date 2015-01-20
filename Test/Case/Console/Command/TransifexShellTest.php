<?php
App::uses('TransifexShell', 'Transifex.Console/Command');

class TransifexShellTest extends CakeTestCase {

	public $TransifexShell;

	public function setUp() {
		parent::setUp();

		CakePlugin::load(array(
				'Transifex'
			));

		$this->TransifexShell = new TransifexShell();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->TransifexShell));
		$this->assertInstanceOf('TransifexShell', $this->TransifexShell);
	}

	//TODO
}
