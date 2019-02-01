<?php
namespace Transifex\Test\TestCase\Shell;

use Cake\TestSuite\TestCase;
use Transifex\Shell\TransifexShell;

class TransifexShellTest extends TestCase {

	/**
	 * @var \Transifex\Shell\TransifexShell
	 */
	public $TransifexShell;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->TransifexShell = new TransifexShell();
	}

	/**
	 * @return void
	 */
	public function testObject() {
		$this->assertTrue(is_object($this->TransifexShell));
		$this->assertInstanceOf('Transifex\Shell\TransifexShell', $this->TransifexShell);
	}

	//TODO

}
