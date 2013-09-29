<?php
App::uses('AppShell', 'Console/Command');
App::uses('TransifexLib', 'Transifex.Lib');

/**
 * Wrapper to get Transifex information and import translation PO files.
 * Use verbose output to display more detailed information.
 *
 * @version 1.0
 * @cakephp 2.x
 * @author Mark Scherer
 * @license MIT
 */
class TransifexShell extends AppShell {

	public $Transifex;

	public function startup() {
		parent::startup();

		$settings = array();
		if (!empty($this->params['project'])) {
			$settings['project'] = $this->params['project'];
		}
		$this->Transifex = new TransifexLib($settings);
	}

	/**
	 * TransifexShell::resources()
	 *
	 * @return void
	 */
	public function resources() {
		$resources = $this->Transifex->getResources();
		foreach ($resources as $resource) {
			$this->out(' - ' . $resource['slug'] . ' (' . $resource['i18n_type'] . ')');
			$this->out('   source language code: ' . $resource['source_language_code'], 1, Shell::VERBOSE);
		}
	}

	/**
	 * TransifexShell::languages()
	 *
	 * @return void
	 */
	public function languages() {
		$languages = $this->_languages();
		foreach ($languages as $language) {
			$res = $this->_getCatalog($language);
			if (!empty($res['language'])) {
				$language .= ' | ' . $res['language'];
			}
			$this->out(' - ' . $language);
			if (!empty($res['locale'])) {
				$this->out('   locale: ' . $res['locale'], 1, Shell::VERBOSE);
			}
		}
	}

	/**
	 * TransifexShell::statistics()
	 *
	 * @return void
	 */
	public function statistics() {
		$this->out('Project: ' . $this->Transifex->settings['project'], 2);

		$resource = $language = null;
		if (!empty($this->args[0])) {
			$resource = $this->args[0];
		}
		if (!empty($this->args[1])) {
			$language = $this->args[1];
		}
		if (empty($resource)) {
			//TODO: prompt for resource here
			$this->error('Please provide a resource - and optionally a language.');
		}

		$stats = $this->Transifex->getStats($resource, $language);
		if ($language) {
			$stats = array($language => $stats);
		}

		foreach ($stats as $language => $stat) {
			$this->out('*** ' . $language . ' ***');
			$translated = $stat['translated_entities'];
			$total = $stat['translated_entities'] + $stat['untranslated_entities'];
			$this->out('Translated: ' . $stat['completed'] . ' (' . $translated . ' of ' . $total . ')');

			$this->out();
		}
	}

	/**
	 * TransifexShell::pull()
	 *
	 * @return void
	 */
	public function pull() {
		$options = $availableLanguages = $this->_languages();
		$options[] = '*';

		$questioning = false;

		if (!empty($this->params['language'])) {
			$language = $this->params['language'];
		} else {
			$language = $this->in('Language', $options, '*');
			$questioning = true;
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
			$questioning = true;
		}
		if (!in_array($resource, $options, true)) {
			$this->error('No such resource');
		}

		if ($resource === '*') {
			$resources = $availableResources;
		} else {
			$resources = (array)$resource;
		}

		$approvedOnly = false;
		if ($questioning && !$this->params['reviewed-only']) {
			$approvedOnly = $this->in('Only reviewed translations', array('y', 'n'), 'n') === 'y';
		} else {
			$approvedOnly = $this->params['reviewed-only'];
		}

		$count = 0;
		foreach ($languages as $language) {
			foreach ($resources as $resource) {
				$this->out(__('Generating PO file for ' . $language . ' and ' . $resource), 1, Shell::VERBOSE);

				$translations = $this->Transifex->getTranslations($resource, $language, $approvedOnly);
				if (empty($translations['content'])) {
					$this->err(' - no PO file for ' . $language . ' and ' . $resource);
					continue;
				}

				$locale = $this->_getLocale($language);

				$path = !empty($this->params['plugin']) ? CakePlugin::path($this->params['plugin']) : APP;
				$file = $path . 'Locale' . DS . $locale . DS . 'LC_MESSAGES' . DS . $resource . '.po';
				$dir = dirname($file);
				if (!is_dir($dir)) {
					if (!mkdir($dir, 0770, true)) {
						$this->exit(__('Cannot create new Locale folder %s', str_replace(APP, DS, $dir)));
					}
				}
				if (empty($this->params['dry-run']) && !file_put_contents($file, $translations['content'])) {
					$this->error(__('Could not store translation content into PO file.'));
				}
				$count++;
				$this->out(__('PO file %s generated', str_replace(APP, DS, $file)), 1, Shell::VERBOSE);
			}
		}

		$this->out('... Done! ' . $count . ' PO file(s) generated.');
	}

	/**
	 * TransifexShell::_getLocale()
	 *
	 * @param string $language
	 * @return string Locale
	 */
	protected function _getLocale($language) {
		$locale = $language;
		$catalog = $this->_getCatalog($language);
		if (!empty($catalog['locale'])) {
			$locale = $catalog['locale'];
		}
		return $locale;
	}

	/**
	 * TransifexShell::_getCatalog()
	 *
	 * @param mixed $language
	 * @return array Catalog
	 */
	protected function _getCatalog($language) {
		if (!isset($this->L10n)) {
			$this->L10n = new L10n();
		}
		$catalog = $this->L10n->catalog(strtolower(str_replace('_', '-', $language)));
		if (!empty($catalog['locale'])) {
			return $catalog;
		} elseif (strpos($language, '_')) {
			list($languagePrefix) = explode('_', $language);
			$catalog = $this->L10n->catalog($languagePrefix);
			if (!empty($catalog['locale'])) {
				return $catalog;
			}
		}
		return array();
	}

	/**
	 * TransifexShell::_resources()
	 *
	 * @return array
	 */
	protected function _resources() {
		$ret = array();
		$resources = $this->Transifex->getResources();
		foreach ($resources as $resource) {
			$ret[] = $resource['slug'];
		}
		sort($ret);
		return $ret;
	}

	/**
	 * TransifexShell::_languages()
	 *
	 * @return array
	 */
	protected function _languages() {
		$translations = $this->Transifex->getProject();
		if (!isset($translations['teams'])) {
			$this->error('No such project');
		}
		sort($translations['teams']);
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
				'project' => array(
					'short' => 'P',
					'help' => __d('cake_console', 'Project'),
					'default' => '',
				),
			)
		);
		$subcommandParserPull = $subcommandParser;
		$subcommandParserPull['options'] += array(
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
				'reviewed-only' => array(
					'short' => 'R',
					'help' => __d('cake_console', 'Only reviewed translations'),
					'boolean' => true,
				),
				'plugin' => array(
					'short' => 'p',
					'help' => __d('cake_console', 'Plugin'),
					'default' => ''
				),
				'dry-run' => array(
					'short' => 'd',
					'help' => __d('cake_console', 'Dry run the command, no files will actually be modified. Should be combined with verbose!'),
					'boolean' => true
				),
		);

		return parent::getOptionParser()
			->description(__d('cake_console', "The Convert Shell converts files from dos/unix/mac to another system"))
			->addSubcommand('resources', array(
				'help' => __d('cake_console', 'List all resources'),
				'parser' => $subcommandParser
			))
			->addSubcommand('languages', array(
				'help' => __d('cake_console', 'List all languages'),
				'parser' => $subcommandParser
			))
			->addSubcommand('statistics', array(
				'help' => __d('cake_console', 'Display project statistics'),
				'parser' => $subcommandParser
			))
			->addSubcommand('pull', array(
				'help' => __d('cake_console', 'Pull PO files'),
				'parser' => $subcommandParserPull
			));
	}

}
