# TYPO3 migration tools
Tools for migrating TYPO3 sites

## Are TYPO3 migration tools helping you to migrate your TYPO3 site more smoothly?
Then please consider a sponsorship so I can make this tool even more awesome!
- Become a patreon on [Patreon](https://www.patreon.com/michielroos)
- Make a donation via [PayPal](https://paypal.me/MichielRoos)

Thank you! ♥

## Requirements
The tool requires **PHP 5.6** or higher to run.

## Installation
Download the latest version from: https://github.com/Tuurlijk/typo3migrate/releases

Or install using composer (skip the init step if you're installing it into an existing project):
```bash
composer init
composer require --update-no-dev  "michielroos/typo3migrate:*"
```

## Usage
Current tools:
* xml2xlf
* fluidNsToHtml
### Convert xml to xlf
Convert a xmllang file to xlf.
```bash
php ./typo3migrate.phar xml2xlf ~/tmp/localllang_db.xml
```
Or all files in a folder:
```bash
php ./typo3migrate.phar xml2xlf ~/tmp/Language
```
### Convert old Fluid namespaces
Convert old Fluid namespaces {brace style} to html tag with attributes.
```html
{namespace f=TYPO3\CMS\Fluid\ViewHelpers}
<section>
</section>

```
Will become:
```html
<html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
	  data-namespace-typo3-fluid="true">
<section>
</section>
</html>
```
Convert a single file:
```bash
php ./typo3migrate.phar fluidNsToHtml ~/tmp/Template.html
```
Or all files in a folder:
```bash
php ./typo3migrate.phar fluidNsToHtml ~/tmp/Templates/
```