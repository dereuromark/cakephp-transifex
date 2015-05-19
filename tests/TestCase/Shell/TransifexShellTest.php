<?php
namespace Transifex\Test\TestCase\Shell;

App::uses('TransifexShell', 'Transifex.Console/Command');

class TransifexShellTest extends CakeTestCase {

	public $TransifexShell;

	public function setUp() {
		parent::setUp();

		CakePlugin::load([
				'Transifex'
			]);

		$this->TransifexShell = new TransifexShell();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->TransifexShell));
		$this->assertInstanceOf('TransifexShell', $this->TransifexShell);
	}

	//TODO
}
