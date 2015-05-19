<?php
namespace Transifex\Test\TestCase\Lib;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Transifex\Lib\TransifexLib;

/**
 *
 */
class TransifexLibTest extends TestCase {

	public $Transifex = null;

	public function setUp() {
		parent::setUp();

		//Configure::write('debug', 2);

		$this->skipIf(!Configure::read('Transifex.user'));

		$settings = [
			'project' => 'cakephp',
		];
		$this->Transifex = new TransifexLib($settings);
	}

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

	public function testGetResources() {
		$res = $this->Transifex->getResources();
		//debug($res);
		$this->assertTrue(count($res) > 2);
		$this->assertTrue(!empty($res[0]['i18n_type']));
	}

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

	public function testGetTranslations() {
		$res = $this->Transifex->getTranslations('cake', 'de');
		//debug($res);
		$this->assertEquals('text/x-po', $res['mimetype']);
		$this->assertTextContains('Plural-Forms: nplurals=2; plural=(n != 1);', $res['content']);
	}

	public function testGetStats() {
		$res = $this->Transifex->getStats('cake');
		//debug($res);
		$this->assertTrue(count($res) > 6);
		$this->assertTrue(!empty($res['de']['reviewed_percentage']));

		$res = $this->Transifex->getStats('cake', 'de');
		$this->assertTrue(!empty($res['reviewed_percentage']));
	}

	public function testPutResource() {
		$file = dirname(__FILE__) . '/../../test_files/test.pot';
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

	public function testPutTranslations() {
		$file = dirname(__FILE__) . '/../../test_files/test.pot';
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
