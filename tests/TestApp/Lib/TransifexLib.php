<?php
namespace TestApp\Lib;

use Cake\Core\Plugin;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Transifex\Lib\TransifexLib as BaseTransifexLib;

/**
 * Transifex API wrapper class.
 *
 * @license MIT
 * @author Mark Scherer
 */
class TransifexLib extends BaseTransifexLib {

	/**
	 * @param string $url
	 * @return array
	 */
	protected function _get($url) {
		$slug = Text::insert($url, $this->settings, ['before' => '{', 'after' => '}']);
		$slug = str_replace(static::BASE_URL, '', $slug);
		$slug = 'GET_' . Inflector::slug($slug);

		$file = Plugin::path('Transifex') . 'tests/test_files/json/' . $slug . '.json';
		if (!$this->settings['debug'] && file_exists($file)) {
			$content = file_get_contents($file);
			return json_decode($content, true);
		}

		$result = parent::_get($url);

		if ($this->settings['debug']) {
			$file = Plugin::path('Transifex') . 'tests/test_files/json/' . $slug . '.json';
			file_put_contents($file, json_encode($result, JSON_OPTIONS));
		}

		return $result;
	}

	/**
	 * @param string $url
	 * @param mixed $data
	 * @param string $requestType
	 * @return mixed
	 * @author   Gustav Wellner Bou <wellner@solutica.de>
	 */
	protected function _post($url, $data, $requestType = 'POST') {
		$slug = Text::insert($url, $this->settings, ['before' => '{', 'after' => '}']);
		$slug = str_replace(static::BASE_URL, '', $slug);
		$slug = strtolower($requestType) . '_' . Inflector::slug($slug) . '_' . md5($data);
		$file = Plugin::path('Transifex') . 'tests/test_files/json/' . $slug . '.json';
		if (!$this->settings['debug'] && file_exists($file)) {
			$content = file_get_contents($file);
			return json_decode($content, true);
		}

		$result = parent::_post($url, $data, $requestType);

		if ($this->settings['debug']) {
			$file = Plugin::path('Transifex') . 'tests/test_files/json/' . $slug . '.json';
			file_put_contents($file, json_encode($result, JSON_OPTIONS));
		}

		return $result;
	}

}
