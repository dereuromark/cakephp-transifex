# CakePHP Transifex Plugin

A Cake2.x Plugin that works with Transifex and pulls translations.

Please note: New functionality has been tested against cake2.3 only. Please upgrade if possible.

## Installation
Installing the Plugin is pretty much as with every other CakePHP Plugin.

* Put the files in `APP/Plugin/Transifex`
* Make sure you have `CakePlugin::load('Transifex')` or `CakePlugin::loadAll()` in your bootstrap

Put the information regarding project, user and password in your config file or your bootstrap

	$config['Transifex'] = array('project' => 'cakephp', 'user' => '...', 'password' => '...');

That's it. It should be up and running.

## Usage

To get a list of supported resources for the current project:

	cake Transifex.Transifex resources

To get a list of supported languages:

	cake Transifex.Transifex languages

To actually update your Locale folder, use

	cake Transifex.Transifex pull

It will prompt for language and resource (use `*` to import all).

A shortcut to import a specific language for a specific resource, you can also use

	cake Transifex.Transifex pull -l {language} -r {resource}

Tip: If you want to dry-run it first, use `-d -v`. This will not modify your locale files but simulate the import.

The PO files will be stored in `APP/Locale/{locale}/LC_MESSAGES/{resource}.po`.
Using subversion or git is highly reocmmended to quickly overview and confirm the changes made.

### Advanced usage

To pull locales for a plugin you need to set `--plugin` or `-p`:

	cake Transifex.Transifex pull -p Tools

If you happen to have one primary project and several other (plugin/cakecore) projects, you can overwrite the config project setting using `--project` or `-o`:

	cake Transifex.Transifex pull -o cakephp

## Disclaimer
License: MIT

Use at your own risk. Please provide any fixes or enhancements via issue or better pull request.
