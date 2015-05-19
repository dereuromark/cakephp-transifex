<?php
namespace Transifex\Test\TestCase\Lib;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use TestApp\Lib\TransifexLib;

/**
 * Testing TransifexLib class
 */
class TransifexLibTest extends TestCase {

	public $Transifex = null;

	/**
	 * @return bool
	 */
	protected static function isDebug() {
		return !empty($_SERVER['argv']) && in_array('--debug', $_SERVER['argv'], true);
	}

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		Configure::write('App.namespace', 'TestApp');

		if ($this->isDebug()) {
			Configure::write('Transifex.debug', true);
		}

		$settings = [
			'project' => 'cakephp',
		];
		$this->Transifex = new TransifexLib($settings);
	}

	/**
	 * @return void
	 */
	public function testGetProject() {
		$res = $this->Transifex->getProject();
		//debug($res);
		$this->assertEquals('en', $res['source_language_code']);
		$this->assertEquals('http://cakephp.org/', $res['homepage']);
		$this->assertSame(false, $res['private']);

		// languages
		$this->assertTrue(!empty($res['teams']));

		$this->assertTrue(!empty($res['maintainers']));
		$this->assertTrue(!empty($res['resources']));
	}

	/**
	 * @return void
	 */
	public function testGetResources() {
		$res = $this->Transifex->getResources();
		//debug($res);
		$this->assertTrue(count($res) > 2);
		$this->assertTrue(!empty($res[0]['i18n_type']));
	}

	/**
	 * @return void
	 */
	public function testGetResource() {
		$res = $this->Transifex->getResource('cake');
		//debug($res);
		$this->assertEquals('PO', $res['i18n_type']);
		$this->assertEquals('en', $res['source_language_code']);
	}

	public function _testGetLanguages() {
		$res = $this->Transifex->getLanguages();
		debug($res);
	}

	public function _testGetLanguage() {
		$res = $this->Transifex->getLanguage('de');
		debug($res);
	}

	/**
	 * @return void
	 */
	public function testGetTranslations() {
		$res = $this->Transifex->getTranslations('cake', 'de');
		$this->assertEquals('text/x-po', $res['mimetype']);
		$this->assertTextContains('Plural-Forms: nplurals=2; plural=(n != 1);', $res['content']);
	}

	/**
	 * @return void
	 */
	public function testGetStats() {
		$res = $this->Transifex->getStats('cake');
		//debug($res);
		$this->assertTrue(count($res) > 6);
		$this->assertTrue(!empty($res['de']['reviewed_percentage']));

		$res = $this->Transifex->getStats('cake', 'de');
		$this->assertTrue(!empty($res['reviewed_percentage']));
	}

	/**
	 * @return void
	 */
	public function testPutResource() {
		$file = Plugin::path('Transifex')  . 'tests/test_files/test.pot';
		$this->assertTrue(is_file($file));
		$resource = 'foo';

		$this->Transifex = $this->getMock('Transifex\Lib\TransifexLib', ['_post'], [$this->Transifex->settings]);
		$mockedResponse = [
			'added' => 1,
		];
		$this->Transifex->expects($this->any())
			->method('_post')
			->will($this->returnValue($mockedResponse));

		$result = $this->Transifex->putResource($resource, $file);
		$this->assertSame($mockedResponse, $result);
	}

	/**
	 * @return void
	 */
	public function testPutTranslations() {
		$file = Plugin::path('Transifex')  . 'tests/test_files/test.pot';
		$this->assertTrue(is_file($file));
		$resource = 'foo';

		$this->Transifex = $this->getMock('Transifex\Lib\TransifexLib', ['_post'], [$this->Transifex->settings]);
		$mockedResponse = [
			'strings_added' => 0,
			'strings_updated' => 0,
			'strings_delete' => 0,
		];
		$this->Transifex->expects($this->any())
			->method('_post')
			->will($this->returnValue($mockedResponse));

		$result = $this->Transifex->putTranslations($resource, 'de', $file);
		$this->assertSame($mockedResponse, $result);
	}

}
