<?php
namespace Transifex\Lib;

App::uses('String', 'Utility');
App::uses('HttpSocket', 'Network/Http');
App::uses('I18n', 'I18n');
App::uses('Inflector', 'Utility');

/**
 * Transifex API wrapper class.
 *
 * @license MIT
 * @author Mark Scherer
 */
class TransifexLib {

	const BASE_URL = 'https://www.transifex.com/api/2/';

	public $settings = [
		'project' => '',
		'user' => '',
		'password' => '',
		'debug' => false, // Verbose debugging for curl (when putting)
	];

	/**
	 * TransifexLib::__construct()
	 *
	 * @param array $settings
	 * @throws RuntimeException Exception.
	 */
	public function __construct($settings = []) {
		$configSettings = (array)Configure::read('Transifex');
		$this->settings = array_merge($this->settings, $configSettings, $settings);

		if (empty($this->settings['project'])) {
			throw new RuntimeException('Project missing');
		}
		if (empty($this->settings['user']) || empty($this->settings['password'])) {
			throw new RuntimeException('Credentials missing');
		}
	}

	/**
	 * TransifexLib::getProject()
	 *
	 * @return array
	 */
	public function getProject() {
		$url = static::BASE_URL . 'project/{project}/?details';
		return $this->_get($url);
	}

	/**
	 * TransifexLib::getResources()
	 *
	 * @return array
	 */
	public function getResources() {
		$url = static::BASE_URL . 'project/{project}/resources/';
		return $this->_get($url);
	}

	/**
	 * TransifexLib::getResource()
	 *
	 * @param mixed $resource
	 * @return array
	 */
	public function getResource($resource) {
		if ($resource) {
			$resource .= '/';
		}
		$url = static::BASE_URL . 'project/{project}/resource/' . $resource;
		return $this->_get($url);
	}

	/**
	 * TransifexLib::getLanguages()
	 * Only the project owner or the project maintainers can perform this action.
	 *
	 * @return array
	 */
	public function getLanguages() {
		$url = static::BASE_URL . 'project/{project}/languages/';
		return $this->_get($url);
	}

	/**
	 * TransifexLib::getLanguage()
	 * Only the project owner or the project maintainers can perform this action.
	 *
	 * @param $language
	 * @return array
	 */
	public function getLanguage($language) {
		$url = static::BASE_URL . 'project/{project}/language/' . $language . '/?details';
		return $this->_get($url);
	}

	/**
	 * TransifexLib::getLanguageInfo()
	 *
	 * @param $language
	 * @return array
	 */
	public function getLanguageInfo($language) {
		$url = static::BASE_URL . 'language/' . $language . '/';
		return $this->_get($url);
	}

	/**
	 * TransifexLib::getTranslations()
	 *
	 * @param mixed $resource
	 * @param mixed $language
	 * @param bool $reviewedOnly
	 * @return array
	 */
	public function getTranslations($resource, $language, $reviewedOnly = false) {
		$url = static::BASE_URL . 'project/{project}/resource/' . $resource . '/translation/' . $language . '/';
		if ($reviewedOnly) {
			$url .= '?mode=reviewed';
		}
		return $this->_get($url);
	}

	/**
	 * TransifexLib::putTranslations()
	 *
	 * @param $resource
	 * @param $language
	 * @param $file
	 * @throws RuntimeException
	 * @throws Exception
	 * @throws RuntimeException
	 * @return mixed
	 * @author Gustav Wellner Bou <wellner@solutica.de>
	 */
	public function putTranslations($resource, $language, $file) {
		$url = static::BASE_URL . 'project/{project}/resource/' . $resource . '/translation/' . $language;
		if (function_exists('curl_file_create') && function_exists('mime_content_type')) {
			$body = ['file' => curl_file_create($file, $this->_getMimeType($file), pathinfo($file, PATHINFO_BASENAME))];
		} else {
			$body = ['file' => '@' . $file];
		}

		try {
			return $this->_post($url, $body, 'PUT');
		} catch(RuntimeException $e) {
			/* Handling a very specific exception due to a Transifex bug */

			// Exception is thrown maybe just because the file only has empty translations
			if (strpos($e->getMessage(), "We're not able to extract any string from the file uploaded for language") !== false) {

				$catalog = I18n::loadPo($file);
				unset($catalog['']);

				if (count($catalog)) {
					if (count(array_filter($catalog)) === 0) {
						// PO file contains empty translations
						// In that case Transifex throws an error although its not.

						// Then we could just append one non empty translation to that file and send it again
						// But apart from successfully sending this file again, it wont affect the remote translations
						return [
							'strings_added' => 0,
							'strings_updated' => 0,
							'strings_delete' => 0,
						];
					} else {
						throw new RuntimeException(sprintf('Could not extract any string from %s. Whereas file contains non-empty translation(s) for following key(s): %s.', $file, '"' . implode('", "', array_keys(array_filter($catalog))) . '"'));
					}
				} else {
					throw new RuntimeException(sprintf('Could not extract any string from %s. File seems empty.', $file));
				}

			} else {
				throw $e;
			}
		}
	}

	/**
	 * TransifexLib::putResource()
	 *
	 * @param $resource
	 * @param $file
	 * @return mixed
	 * @author Gustav Wellner Bou <wellner@solutica.de>
	 */
	public function putResource($resource, $file) {
		$url = static::BASE_URL . 'project/{project}/resource/' . $resource . '/content';
		if (function_exists('curl_file_create')) {
			$body = ['file' => curl_file_create($file, $this->_getMimeType($file), pathinfo($file, PATHINFO_BASENAME))];
		} else {
			$body = ['file' => '@' . $file];
		}

		return $this->_post($url, $body, 'PUT');
	}

	/**
	 * TransifexLib::createResource()
	 *
	 * @param $resource
	 * @param $file
	 * @return mixed
	 * @author Marco Beinbrech <marco.beinbrech@fotograf.de>
	 */
	public function createResource($resource, $file) {
		$url = static::BASE_URL . 'project/{project}/resources';

		$body = [
			'name' => $resource,
			'slug' => Inflector::slug($resource),
			'i18n_type' => 'PO'
		];

		if (function_exists('curl_file_create')) {
			$body['file'] = curl_file_create($file, $this->_getMimeType($file), pathinfo($file, PATHINFO_BASENAME));
		} else {
			$body['file'] = '@' . $file;
		}

		return $this->_post($url, $body);
	}

	/**
	 * @param string $file
	 * @return string
	 */
	protected function _getMimeType($file) {
		if (!function_exists('finfo_open')) {
			if (!function_exists('mime_content_type')) {
				throw new InternalErrorException('At least one of finfo or mime_content_type() needs to be available');
			}
			return mime_content_type($file);
		}
		$finfo = finfo_open(FILEINFO_MIME);
		$mimetype = finfo_file($finfo, $file);
		return $mimetype;
	}

	/**
	 * TransifexLib::getStats()
	 *
	 * @param string $resource
	 * @param string $language
	 * @return array
	 */
	public function getStats($resource, $language = null) {
		if ($language) {
			$language .= '/';
		}
		$url = static::BASE_URL . 'project/{project}/resource/' . $resource . '/stats/' . $language;
		return $this->_get($url);
	}

	/**
	 * TransifexLib::_get()
	 *
	 * @param string $url
	 * @return array
	 * @throws RuntimeException Exception.
	 */
	protected function _get($url) {
		$Socket = new HttpSocket();
		$Socket->configAuth('Basic', $this->settings['user'], $this->settings['password']);

		$url = String::insert($url, $this->settings, ['before' => '{', 'after' => '}']);
		$response = $Socket->get($url);
		if (!$response->isOk()) {
			throw new RuntimeException('Unable to retrieve data from API');
		}
		return json_decode($response->body(), true);
	}

	/**
	 * TransifexLib::_post()
	 *
	 * @param $url
	 * @param $data
	 * @param string $requestType
	 * @throws RuntimeException
	 * @internal param $post
	 * @return mixed
	 * @author   Gustav Wellner Bou <wellner@solutica.de>
	 */
	protected function _post($url, $data, $requestType = 'POST') {
		$error = false;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, String::insert($url, $this->settings, ['before' => '{', 'after' => '}']));
		curl_setopt($ch, CURLOPT_USERPWD, $this->settings['user'] . ":" . $this->settings['password']);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $requestType);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		if ($this->settings['debug']) {
			curl_setopt($ch, CURLOPT_VERBOSE, true);
		}
		$result = curl_exec($ch);
		$info = curl_getinfo($ch);

		if (($errMsg = curl_error($ch)) || !in_array((int)$info['http_code'], [200, 201])) {
			$error = true;
		}

		curl_close($ch);

		if ($error) {
			throw new RuntimeException('Unable to send data to API (' . $errMsg . ')');
		}

		return json_decode($result, true);
	}

}
