<?php

namespace ArmCyber\Localization\Services;

use ArmCyber\Localization\Storages\SessionStorage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Symfony\Component\Translation\Loader\MoFileLoader;
use Symfony\Component\Translation\Loader\PoFileLoader;
use Symfony\Component\Translation\Translator;
use RuntimeException;

class LocalizationService
{
    private const CONFIG_PREFIX = 'localization.';

    private Translator $translator;
    private SessionStorage $storage;

    private $locale;
    private $domain;
    private $savedLocale;
    private $resources = [];
    private $cachedPackages = [];


    public function __construct()
    {
        $this->registerTranslator();
    }

    /**
     * Get configuration.
     *
     * @param $key
     * @param $default
     * @return mixed
     */
    public function config($key, $default = null)
    {
        return config(self::CONFIG_PREFIX . $key, $default);
    }

    /**
     * Register translator.
     *
     * @return void
     */
    private function registerTranslator()
    {
        $this->storage = new SessionStorage($this);
        $locale = $this->storage->getLocale();
        $this->translator = new Translator($locale);
        $this->translator->setFallbackLocales([$this->config('fallback_locale')]);
        $this->translator->addLoader('mo', new MoFileLoader());
        $this->translator->addLoader('po', new PoFileLoader());
        $this->domain = $this->storage->getDomain();
        $this->adjustLocale($locale);
    }

    /**
     * Get current locale.
     *
     * @return mixed
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set locale.
     *
     * @param $locale
     * @return void
     */
    public function setLocale($locale)
    {
        if (!$this->isLocaleSupported($locale) || $this->isCurrentLocale($locale)) {
            return;
        }
        $this->storage->setLocale($locale);
        $this->translator->setLocale($locale);
        $this->adjustLocale($locale);
    }

    /**
     * Adjust locale.
     *
     * @param $locale
     * @return void
     */
    private function adjustLocale($locale)
    {
        $this->locale = $locale;
        App::setLocale(Str::before($locale, '_'));
        $this->loadResource($locale);
    }

    /**
     * Get supported locales.
     *
     * @return mixed
     */
    public function getSupportedLocales()
    {
        return $this->config('locales', []);
    }

    /**
     * Check if locale is supported.
     *
     * @param $locale
     * @return bool
     */
    public function isLocaleSupported($locale)
    {
        return $locale !== null && in_array($locale, $this->getSupportedLocales());
    }

    /**
     * Get translation file.
     *
     * @param $domain
     * @param $locale
     * @param $extension
     * @return string
     */
    private function getFile($domain, $locale, $extension)
    {
        $path = trim($this->config('translations_path_name'), '\\/');
        $subPath = trim($this->config('translations_sub_path_name'), '\\/');
        return App::langPath() . "/{$path}/{$locale}/{$subPath}/{$domain}.{$extension}";
    }

    /**
     * Load translation resource.
     *
     * @return void
     */
    private function loadResource($locale)
    {
        if (isset($this->resources[$this->domain][$locale])) {
            return;
        }
        $fileMo = $this->getFile($this->domain, $locale, 'mo');
        if (file_exists($fileMo)) {
            $this->translator->addResource('mo', $fileMo, $locale, $this->domain);
        } else {
            $filePo = $this->getFile($this->domain, $locale, 'po');
            $this->translator->addResource('po', $filePo, $locale, $this->domain);
        }

        $this->resources[$this->domain][$locale] = true;
    }

    /**
     * Insert translation arguments.
     *
     * @param $translation
     * @param $args
     * @return mixed|string
     */
    private function insertArguments($translation, $args)
    {
        return $translation ? vsprintf($translation, Arr::wrap($args)) : $translation;
    }

    /**
     * Translate text.
     *
     * @param $message
     * @param $args
     * @param $locale
     * @param $domain
     * @return mixed|string
     */
    public function translate($message, $args = null, $locale = null, $domain = null)
    {
        if (!$locale) {
            $locale = $this->locale;
        }
        if (!$domain) {
            $domain = $this->domain;
        }
        $translation = $this->originalTranslate($message, $domain, $locale);
        if ($message == $translation) {
            $fallbackLocale = $this->config("custom_fallbacks.{$locale}");
            if ($this->isLocaleSupported($fallbackLocale)) {
                if (!isset($this->resources[$this->domain][$fallbackLocale])) {
                    $this->loadResource($fallbackLocale);
                }
                $translation = $this->originalTranslate($message, $domain, $fallbackLocale);
            }
        }
        if (!empty($args)) {
            $translation = $this->insertArguments($translation, $args);
        }
        return $translation;
    }

    /**
     * Translate using Symfony.
     *
     * @param $message
     * @param $domain
     * @param $locale
     * @return mixed|string
     */
    private function originalTranslate($message, $domain, $locale)
    {
        $translation = $this->translator->trans($message, [], $domain, $locale);
        if ($translation === '') {
            $translation = $message;
        }
        return $translation;
    }

    /**
     * Translate plural text.
     *
     * @param $singularMessage
     * @param $pluralMessage
     * @param $count
     * @param $args
     * @param $locale
     * @param $domain
     * @return mixed|string
     */
    public function translatePlural($singularMessage, $pluralMessage, $count, $args = null, $locale = null, $domain = null)
    {
        $message = $count > 1 ? $pluralMessage : $singularMessage;
        return $this->translate($message, $args, $locale, $domain);
    }

    /**
     * Get default locale.
     *
     * @return string
     */
    public function getDefaultLocale()
    {
        return $this->config('locale');
    }

    /**
     * Temporary save locale if you need to use different locale then revert.
     *
     * @return void
     */
    public function saveLocale()
    {
        $this->savedLocale = $this->getLocale();
    }

    /**
     * Apply previously saved locale.
     *
     * @return void
     */
    public function applySavedLocale()
    {
        if (!isset($this->savedLocale)) {
            return;
        }
        $this->setLocale($this->savedLocale);
        unset($this->savedLocale);
    }

    /**
     * Execute a function with custom locale.
     *
     * @param $locale
     * @param callable $callback
     * @return mixed
     */
    public function withLocale($locale, callable $callback)
    {
        if (!$this->isLocaleSupported($locale) || $this->isCurrentLocale($locale)) {
            return $callback();
        }

        $originalLocale = $this->getLocale();
        try {
            $this->setLocale($locale);
            $result = $callback();
        } finally {
            $this->setLocale($originalLocale);
        }

        return $result;
    }

    /**
     * Check if current locale is the locale.
     *
     * @param $locale
     * @return bool
     */
    public function isCurrentLocale($locale)
    {
        return $locale == $this->getLocale();
    }

    /**
     * Get HTML of the view with availability of using custom locale.
     *
     * @param $view
     * @param mixed $params
     * @param $customLocale
     * @return mixed
     */
    public function getView($view, $params = [], $customLocale = null)
    {
        return $this->withLocale($customLocale, function () use ($view, $params) {
            return view($view, $params)->render();
        });
    }

    /**
     * Validate locale and get, if not supported get current locale.
     *
     * @param $locale
     * @return mixed
     */
    public function getValidatedLocale($locale = null)
    {
        return $this->isLocaleSupported($locale) ? $locale : $this->getLocale();
    }

    /**
     * Get supported locales except some.
     *
     * @param array $locales
     * @return mixed
     */
    public function getSupportedLocalesExcept($locales = [])
    {
        return array_diff($this->getSupportedLocales(), $locales);
    }

    /**
     * Get the package info.
     *
     * @param $package
     * @param $locale
     * @return mixed
     */
    public function getPackage($package, $locale = null)
    {
        $package = trim($package, "\/\\ \t\n\r\0\x0B");
        $locale = $this->getValidatedLocale($locale);

        if (!isset($this->cachedPackages[$package][$locale])) {
            $packagesPathName = $this->config('packages_path_name');
            if ($packagesPathName || in_array($packagesPathName, ['\\', '/'])) {
                $packagesPathName = Str::start($packagesPathName, DIRECTORY_SEPARATOR);
            }
            $path = App::langPath() . "{$packagesPathName}/{$package}.php";

            if (!file_exists($path)) {
                throw new RuntimeException("Package {$package} don't found.");
            }

            $this->cachedPackages[$package][$locale] = $this->withLocale($locale, function () use ($path) {
                return require $path;
            });
        }

        return $this->cachedPackages[$package][$locale];
    }

    /**
     * Get specific message form package.
     * @param $package
     * @param $messageKey
     * @param array $args
     * @param null $customLocale
     * @return mixed
     */
    public function getMessage($package, $messageKey, $args = [], $customLocale = null)
    {
        $package = $this->getPackage($package, $customLocale);
        $message = $package[$messageKey] ?? null;

        if (!empty($args)) {
            $message = $this->insertArguments($message, $args);
        }

        return $message;
    }

    public function getUnusedTerms() {
        $locales = $this->getSupportedLocales();
        foreach ($locales as $locale) {
            $translator = new Translator($locale);
            $translator->addLoader('po', new PoFileLoader());
            $file = $this->getFile($this->domain, $locale, 'po');
            $translator->addResource('po', $file, $locale, $this->domain);
            $terms = $translator->getCatalogue($locale)->all($this->domain);
            $unusedTermsForLocale = collect($terms)->filter(fn($t) => !$t)->keys()->all();
            if (!isset($unusedTerms)) {
                $unusedTerms = $unusedTermsForLocale;
            } else {
                $unusedTerms = array_filter($unusedTerms, fn($x) => in_array($x, $unusedTermsForLocale));
            }
        }
        return array_values($unusedTerms);
    }
}
