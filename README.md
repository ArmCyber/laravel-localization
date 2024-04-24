# ArmCyber Localization package

## Required tools
1. gettext (on Ubuntu, just run `sudo apt install gettext`)

## Installation
1. Register in POEditor and obtain your API token and Project ID.
2. Add this repository to your `composer.json` repositories. (Note: This package is not published to Packagist). 
3. Run `composer require armcyber/localization`
4. Fill `Key` and `Secret` you got on `13.` point 
5. Add `POEDITOR_API_TOKEN` and `POEDITOR_PROJECT_ID` to your .env

# Usage
1. `Localization::getLocale()` - Get the locale
2. `Localization::setLocale($locale)` - Set the locale
3. `_t('message')` - Get the translation
4. Command `php artisan ts:export` - Export terms to POEditor
5. Command `php artisan ts:import` - Import translations from POEditor