<?php

class TransifexLib {

	const BASE_URL = 'https://www.transifex.com/api/2/';

	public $settings = array(
		'project' => '',
		'user' => '',
		'password' => '',
	);

	public function __construct($settings = array()) {
		$configSettings = (array)Configure::read('Transifex');
		$this->settings = array_merge($this->settings, $configSettings, $settings);

		if (empty($this->settings['project'])) {
			throw new RuntimeException('Project missing');
		}
		if (empty($this->settings['user']) || empty($this->settings['password'])) {
			throw new RuntimeException('Credentials missing');
		}

		if (!function_exists('curl_init')) {
			throw new RuntimeException('cURL needs to be installed/enabled');
		}
	}

	/**
	 * TransifexLib::getProject()
	 *
	 * @return array
	 */
	public function getProject() {
		$url = self::BASE_URL.'project/{project}/?details';
		return $this->_get($url);
	}

	/**
	 * TransifexLib::getResources()
	 *
	 * @return array
	 */
	public function getResources() {
		$url = self::BASE_URL.'project/{project}/resources/';
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
		$url = self::BASE_URL.'project/{project}/resource/' . $resource;
		return $this->_get($url);
	}

	/**
	 * TransifexLib::getLanguages()
	 * Only the project owner or the project maintainers can perform this action.
	 *
	 * @return array
	 */
	public function getLanguages() {
		$url = self::BASE_URL.'project/{project}/languages/';
		return $this->_get($url);
	}

	/**
	 * TransifexLib::getLanguage()
	 * Only the project owner or the project maintainers can perform this action.
	 *
	 * @return array
	 */
	public function getLanguage($language) {
		$url = self::BASE_URL.'project/{project}/language/'.$language.'/?details';
		return $this->_get($url);
	}

	/**
	 * TransifexLib::getTranslations()
	 *
	 * @param mixed $resource
	 * @param mixed $language
	 * @param boolean $reviewedOnly
	 * @return array
	 */
	public function getTranslations($resource, $language, $reviewedOnly = false) {
		$url = self::BASE_URL.'project/{project}/resource/'.$resource.'/translation/'.$language.'/';
		if ($reviewedOnly) {
			$url .= '?mode=reviewed';
		}
		return $this->_get($url);
	}

	public function getStats($resource, $language = null) {
		if ($language) {
			$language .= '/';
		}
		$url = self::BASE_URL . 'project/{project}/resource/' . $resource . '/stats/' . $language;
		return $this->_get($url);
	}

	/**
	 * TransifexLib::_get()
	 *
	 * @param string $url
	 * @return array
	 */
	protected function _get($url) {
		$url = 'curl --silent --user {user}:{password} -X GET ' . $url;
		$url = String::insert($url, $this->settings, array('before' => '{', 'after' => '}'));

		exec($url, $output, $ret);
		if ($output[0] !== '{' && $output[0] !== '[') {
			throw new RuntimeException($output[0]);
			return array();
		}
		$output = json_decode(implode("\n", $output), true);
		return $output;
	}

}