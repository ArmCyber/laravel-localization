<?php

namespace ArmCyber\Localization\Services\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static setLocale($locale)
 * @method static getLocale()
 * @method static getDefaultLocale()
 * @method static getSupportedLocales()
 * @method static isLocaleSupported($locale)
 * @method static translate($message, $args = null, $locale = null, $domain = null)
 * @method static translatePlural($singularMessage, $pluralMessage, $count, $args = null, $locale = null, $domain = null)
 * @method static saveLocale()
 * @method static applySavedLocale()
 * @method static withLocale($locale, callable $callback)
 * @method static isCurrentLocale($locale)
 * @method static getView($view, $params = [], $customLocale = null)
 * @method static getValidatedLocale($locale = null)
 * @method static getSupportedLocalesExcept($locales = [])
 * @method static getPackage($package, $locale = null)
 * @method static getMessage($package, $messageKey, $params = [], $anotherLocale = null)
 *
 * @see \ArmCyber\Localization\Services\LocalizationService
 */
class Localization extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'localization';
    }
}
