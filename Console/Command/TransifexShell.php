<?php
App::uses('AppShell', 'Console/Command');
App::uses('TransifexLib', 'Transifex.Lib');

/**
 * Wrapper to get Transifex information and import translation PO files
 *
 * @version 1.0
 * @cakephp 2.x
 * @author Mark Scherer
 * @license MIT
 * 2013-05-16 ms
 */
class TransifexShell extends AppShell {

	public $Transifex;

	public function startup() {
		$this->Transifex = new TransifexLib();

		parent::startup();
	}

	public function resources() {
		$resources = $this->Transifex->getResources();
		foreach ($resources as $resource) {
			$this->out(' - ' . $resource['slug'] . ' ('.$resource['i18n_type'].')');
		}
	}

	public function languages() {
		$languages = $this->_languages();
		foreach ($languages as $language) {
			$this->out(' - ' . $language);
		}
	}

	public function pull() {
		$options = $availableLanguages = $this->_languages();
		$options[] = '*';

		if (!empty($this->params['language'])) {
			$language = $this->params['language'];
		} else {
			$language = $this->in('Language', $options, '*');
		}
		if (!in_array($language, $options, true)) {
			$this->error('No such language');
		}

		if ($language === '*') {
			$languages = $availableLanguages;
		} else {
			$languages = (array)$language;
		}

		$options = $availableResources = $this->_resources();
		$options[] = '*';

		if (!empty($this->params['resource'])) {
			$resource = $this->params['resource'];
		} else {
			$resource = $this->in('Resource', $options, '*');
		}
		if (!in_array($resource, $options, true)) {
			$this->error('No such resource');
		}

		if ($resource === '*') {
			$resources = $availableResources;
		} else {
			$resources = (array)$resource;
		}

		foreach ($languages as $language) {
			foreach ($resources as $resource) {
				$this->out(__('Generating PO file for ' . $language . ' and ' . $resource), 1, Shell::VERBOSE);

				$translations = $this->Transifex->getTranslations($resource, $language);
				if (empty($translations['content'])) {
					$this->err(' - no PO file for ' . $language . ' and ' . $resource);
					continue;
				}
				$L10n = new L10n();
				$locale = $L10n->map($language);

				$path = !empty($this->params['plugin']) ? CakePlugin::path($this->params['plugin']) : APP;
				$file = $path . 'Locale' . DS . $locale . DS . 'LC_MESSAGES' . DS . $resource . '.po';
				$dir = dirname($file);
				if (!is_dir($dir)) {
					if (!mkdir($dir, 0770, true)) {
						$this->exit(__('Cannot create new Locale folder %s', str_replace(APP, '/', $dir)));
					}
				}
				if (!empty($this->params['dry-run'])) {
					continue;
				}
				if (!file_put_contents($file, $translations['content'])) {
					$this->error(__('Could not store translation content into PO file.'));
				}
			}
		}

		$this->out('...done');
	}

	protected function _resources() {
		$ret = array();
		$resources = $this->Transifex->getResources();
		foreach ($resources as $resource) {
			$ret[] = $resource['slug'];
		}
		return $ret;
	}

	protected function _languages() {
		$translations = $this->Transifex->getProject();
		if (!isset($translations['teams'])) {
			$this->error('No such project');
		}
		return $translations['teams'];
	}

	/**
	 * Get the option parser
	 *
	 * @return ConsoleOptionParser
	 */
	public function getOptionParser() {
		$subcommandParser = array(
			'options' => array(
				'language' => array(
					'short' => 'l',
					'help' => __d('cake_console', 'Language'),
					'default' => ''
				),
				'resource' => array(
					'short' => 'r',
					'help' => __d('cake_console', 'Resource'),
					'default' => '',
				),
				'dry-run'=> array(
					'short' => 'd',
					'help' => __d('cake_console', 'Dry run the command, no files will actually be modified. Should be combined with verbose!'),
					'boolean' => true
				),
			)
		);

		return parent::getOptionParser()
			->description(__d('cake_console', "The Convert Shell converts files from dos/unix/mac to another system"))
			->addSubcommand('resources')
			->addSubcommand('languges')
			->addSubcommand('pull', array(
				'help' => __d('cake_console', 'Pull PO files'),
				'parser' => $subcommandParser
			));
	}

}
