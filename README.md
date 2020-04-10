# CakePHP Transifex Plugin
[![Build Status](https://api.travis-ci.com/dereuromark/cakephp-transifex.svg)](https://travis-ci.com/dereuromark/cakephp-transifex)
[![Coverage Status](https://img.shields.io/codecov/c/github/dereuromark/cakephp-transifex/master.svg)](https://codecov.io/github/dereuromark/cakephp-transifex?branch=master)
[![Minimum PHP Version](http://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-transifex/license.png)](https://packagist.org/packages/dereuromark/cakephp-transifex)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-transifex/d/total.png)](https://packagist.org/packages/dereuromark/cakephp-transifex)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-yellow.svg)](https://github.com/php-fig-rectified/fig-rectified-standards)

A CakePHP 3.x plugin that works with [Transifex](https://www.transifex.com/) and pulls/pushes translations.
It uses the [Transifex API v2](http://docs.transifex.com/developer/api/).

Please note: New functionality has been tested against latest CakePHP 3.x version only. Please upgrade if possible.

## Installation
Installing the plugin is pretty much as with every other CakePHP plugin.

* `composer require dereuromark/cakephp-transifex`
* Make sure you have `Plugin::load('Transifex')` or `Plugin::loadAll()` in your bootstrap

Create an account at transifex.com and create/join a project and resources.

Put the information regarding project, user and password in your config file or your bootstrap
```php
$config['Transifex'] = [
	'project' => 'cakephp',
	'user' => '...',
	'password' => '...'
];
```

That's it. It should be up and running.

## Usage

To get a list of supported resources for the current project:

	cake Transifex.Transifex resources

To get a list of supported languages:

	cake Transifex.Transifex languages

Statistics for a resource can be gathered using

	cake Transifex.Transifex statistics

To actually update your Locale folder, use

	cake Transifex.Transifex pull

It will prompt for language and resource (use `*` to import all).

A shortcut to import a specific language for a specific resource, you can also use

	cake Transifex.Transifex pull -l {language} -r {resource}



The PO files will be stored in `src/Locale/{locale}/LC_MESSAGES/{resource}.po`.
Using version control is highly recommended to quickly overview and confirm the changes made.

### Advanced usage

You can pull reviewed translations only using `--reviewed-only` or `-R`:

	cake Transifex.Transifex pull -R

To pull locales for a plugin you need to set `--plugin` or `-p`:

	cake Transifex.Transifex pull -p Tools

They will then be stored in the plugin's Locale folder.

If you happen to have one primary project and several other (plugin or CakePHP core) projects, you can overwrite the config project setting using `--project` or `-P`:

	cake Transifex.Transifex pull -P cakephp

Tip: If you want to dry-run it first, use `-d -v`. This will not modify your locale files but simulate the import.

### Tips
You can use the auto-alias of `Transifex.Transifex` which is `Transifex` - or even a super-short `t`
using [custom aliasing](http://api.cakephp.org/3.0/class-Cake.Console.ShellDispatcher.html#_alias).

### Debugging
Use `--debug` to have more verbose debugging output when pushing via cURL.

### Testing
When testing `--debug` enables live test mode and uses the actual API instead of mocking and fake JSON response files.
Make sure you provide valid credentials in your `tests/bootstrap.php` for this. Also make sure those
are not your live credentials to avoid data loss. You should use a dedicated test account here to be sure.

## TODO

* More tests

## Disclaimer
Use at your own risk. Please provide any fixes or enhancements via issue or better pull request.
