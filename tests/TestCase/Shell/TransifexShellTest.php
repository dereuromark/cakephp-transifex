<?php
namespace Transifex\Test\TestCase\Shell;

use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use Transifex\Shell\TransifexShell;

class TransifexShellTest extends TestCase {

	public $TransifexShell;

	public function setUp() {
		parent::setUp();

		$this->TransifexShell = new TransifexShell();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->TransifexShell));
		$this->assertInstanceOf('Transifex\Shell\TransifexShell', $this->TransifexShell);
	}

	//TODO
}
