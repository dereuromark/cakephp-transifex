# CakePHP Transifex Plugin

A Cake2.x Plugin that works with Transifex and pulls translations.

Please note: New functionality has been tested against CakePHP v2.3 only. Please upgrade if possible.

## Installation
Installing the Plugin is pretty much as with every other CakePHP Plugin.

* Put the files in `APP/Plugin/Transifex`
* Make sure you have `CakePlugin::load('Transifex')` or `CakePlugin::loadAll()` in your bootstrap

Create an account at transifex.com and create/join a project and resources.

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



The PO files will be stored in `APP/Locale/{locale}/LC_MESSAGES/{resource}.po`.
Using subversion or git is highly recommended to quickly overview and confirm the changes made.

### Advanced usage

You can pull reviewed translations only using `--reviewed-only` or `-R`:

	cake Transifex.Transifex pull -R

To pull locales for a plugin you need to set `--plugin` or `-p`:

	cake Transifex.Transifex pull -p Tools

They will then be stored in the Plugin Locale folder.

If you happen to have one primary project and several other (plugin or CakePHP core) projects, you can overwrite the config project setting using `--project` or `-P`:

	cake Transifex.Transifex pull -P cakephp

Tip: If you want to dry-run it first, use `-d -v`. This will not modify your locale files but simulate the import.

## Disclaimer
License: MIT

Use at your own risk. Please provide any fixes or enhancements via issue or better pull request.
