<?php
/**
 * Group test - Transifex
 */
class AllTransifexTest extends PHPUnit_Framework_TestSuite {

	/**
	 * Suite method, defines tests for this suite.
	 *
	 * @return void
	 */
	public static function suite() {
		$Suite = new CakeTestSuite('All Transifex tests');
		$path = dirname(__FILE__);
		$Suite->addTestDirectory($path . DS . 'Console' . DS . 'Command');
		$Suite->addTestDirectory($path . DS . 'Lib');
		return $Suite;
	}

}
