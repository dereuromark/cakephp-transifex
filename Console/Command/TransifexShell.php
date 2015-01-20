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

		$settings = array(
			'debug' => $this->params['debug']
		);
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
	 * TransifexShell::languages()
	 *
	 * @return void
	 */
	public function language() {
		$lang = !empty($this->args[0]) ? $this->args[0] : null;
		if (!$lang) {
			return $this->error('No language specified, please use two-letter-code, e.g. "de" or "en".');
		}
		$language = $this->Transifex->getLanguage($lang);
		$this->out(print_r($language, true));
	}

	/**
	 * TransifexShell::languages()
	 *
	 * @return void
	 */
	public function language_info() {
		$lang = !empty($this->args[0]) ? $this->args[0] : null;
		if (!$lang) {
			return $this->error('No language specified, please use two-letter-code, e.g. "de" or "en".');
		}
		$language = $this->Transifex->getLanguageInfo($lang);
		$this->out(print_r($language, true));
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
			return $this->error('Please provide a resource - and optionally a language.');
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
			return $this->error('No such language');
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
			return $this->error('No such resource');
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
				$this->out(sprintf('Generating PO file for %s and %s', $language, $resource), 1, Shell::VERBOSE);

				$translations = $this->Transifex->getTranslations($resource, $language, $approvedOnly);
				if (empty($translations['content'])) {
					$this->err(' - ' . sprintf('No PO file for %s and %s', $language, $resource));
					continue;
				}

				$locale = $this->_getLocale($language);

				$path = !empty($this->params['plugin']) ? CakePlugin::path($this->params['plugin']) : APP;
				$file = $path . 'Locale' . DS . $locale . DS . 'LC_MESSAGES' . DS . $resource . '.po';
				$dir = dirname($file);
				if (!is_dir($dir)) {
					if (!mkdir($dir, 0770, true)) {
						return $this->error(sprintf('Cannot create new Locale folder %s', str_replace(APP, DS, $dir)));
					}
				}
				if (empty($this->params['dry-run']) && !file_put_contents($file, $translations['content'])) {
					return $this->error(sprintf('Could not store translation content into PO file (%s).', str_replace(APP, DS, $file)));
				}
				$count++;
				$this->out(sprintf('PO file %s generated', str_replace(APP, DS, $file)), 1, Shell::VERBOSE);
			}
		}

		$this->out('... Done! ' . $count . ' PO file(s) generated.');
	}

	/**
	 * TransifexShell::push()
	 *
	 * @return void
	 * @author Gustav Wellner Bou <wellner@solutica.de>
	 */
	public function push() {
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
			return $this->error(sprintf('No such language \'%s\'.', $language));
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
			return $this->error(sprintf('No such resource \'%s\'.', $language));
		}

		if ($resource === '*') {
			$resources = $availableResources;
		} else {
			$resources = (array)$resource;
		}

		$count = 0;
		foreach ($languages as $language) {
			foreach ($resources as $resource) {
				$this->out('Submitting PO file for ' . $language . ' and ' . $resource, 1, Shell::NORMAL);

				$locale = $this->_getLocale($language);

				$path = !empty($this->params['plugin']) ? CakePlugin::path($this->params['plugin']) : APP;
				$file = $path . 'Locale' . DS . $locale . DS . 'LC_MESSAGES' . DS . $resource . '.po';
				$dir = dirname($file);
				if (!is_file($file)) {
					$this->error(sprintf('PO file not found: %s', str_replace(APP, DS, $file)));
				}
				if (empty($this->params['dry-run']) && !$this->Transifex->putTranslations($resource, $language, $file)) {
					return $this->error('Could not submit translation.');
				}

				$count++;
				$this->out(sprintf('PO file %s submitted', str_replace(APP, DS, $file)), 1, Shell::NORMAL);
			}
		}

		$this->out('... Done! ' . $count . ' PO file(s) pushed.');
	}

	/**
	 * TransifexShell::update()
	 *
	 * @return void
	 * @author Gustav Wellner Bou <wellner@solutica.de>
	 */
	public function update() {
		$options = $availableResources = $this->_resources();
		$options[] = '*';

		if (!empty($this->params['resource'])) {
			$resource = $this->params['resource'];
		} else {
			$resource = $this->in('Resource', $options, '*');
			$questioning = true;
		}
		if (!in_array($resource, $options, true)) {
			return $this->error('No such resource');
		}

		if ($resource === '*') {
			$resources = $availableResources;
		} else {
			$resources = (array)$resource;
		}

		$count = 0;
		foreach ($resources as $resource) {
			$this->out('Submitting POT file for resource ' . $resource, 1, Shell::NORMAL);

			$path = !empty($this->params['plugin']) ? CakePlugin::path($this->params['plugin']) : APP;
			$file = $path . 'Locale' . DS . $resource . '.pot';
			$dir = dirname($file);
			if (!is_file($file)) {
				$this->error(sprintf('POT file not found: %s', str_replace(APP, DS, $file)));
			}
			if (empty($this->params['dry-run']) && !$this->Transifex->putResource($resource, $file)) {
				return $this->error('Could not submit catalog.');
			}

			$count++;
			$this->out(sprintf('POT file %s submitted', str_replace(APP, DS, $file)), 1, Shell::NORMAL);
		}

		$this->out('... Done! ' . $count . ' POT file(s) pushed.');
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
			return $this->error('No such project');
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
					'help' => 'Project',
					'default' => '',
				),
				'debug' => array(
					'help' => 'Debug output (for network/connection details).',
					'boolean' => true
				),
			)
		);
		$subcommandParserPull = $subcommandParser;
		$subcommandParserPull['options'] += array(
			'language' => array(
					'short' => 'l',
					'help' => 'Language',
					'default' => ''
				),
				'resource' => array(
					'short' => 'r',
					'help' => 'Resource',
					'default' => '',
				),
				'reviewed-only' => array(
					'short' => 'R',
					'help' => 'Only reviewed translations',
					'boolean' => true,
				),
				'plugin' => array(
					'short' => 'p',
					'help' => 'Plugin',
					'default' => ''
				),
				'dry-run' => array(
					'short' => 'd',
					'help' => 'Dry run the command, no files will actually be modified. Should be combined with verbose!',
					'boolean' => true
				),
		);

		return parent::getOptionParser()
			->description("The Convert Shell converts files from dos/unix/mac to another system")
			->addSubcommand('resources', array(
				'help' => 'List all resources.',
				'parser' => $subcommandParser
			))
			->addSubcommand('languages', array(
				'help' => 'List all languages.',
				'parser' => $subcommandParser
			))
			->addSubcommand('language', array(
				'help' => 'Get project infos to a specific language.',
				'parser' => $subcommandParser
			))
			->addSubcommand('statistics', array(
				'help' => 'Display project statistics.',
				'parser' => $subcommandParser
			))
			->addSubcommand('pull', array(
				'help' => 'Pull PO files.',
				'parser' => $subcommandParserPull
			))
			->addSubcommand('push', array(
				'help' => 'Push PO files.',
				'parser' => $subcommandParserPull
			))
			->addSubcommand('update', array(
				'help' => 'Update POT files.',
				'parser' => $subcommandParserPull
			))
			->addSubcommand('language_info', array(
				'help' => 'Get infos to a specific language.',
				'parser' => $subcommandParser
			));
	}

}
