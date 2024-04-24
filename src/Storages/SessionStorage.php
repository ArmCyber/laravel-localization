<?php

namespace ArmCyber\Localization\Storages;

use ArmCyber\Localization\Services\LocalizationService;
use Illuminate\Support\Facades\Session;

class SessionStorage
{
    private LocalizationService $service;
    private string              $sessionPrefix;

    public function __construct(LocalizationService $service)
    {
        $this->service = $service;
        $this->sessionPrefix = $service->config('session_prefix');
    }

    /**
     * Get session.
     *
     * @param $key
     * @param $default
     * @return mixed
     */
    private function getSession($key, $default = null)
    {
        return Session::get($this->sessionPrefix . '_' . $key, $default);
    }

    /**
     * Set session.
     *
     * @param $key
     * @param $value
     * @return void
     */
    private function setSession($key, $value)
    {
        Session::put($this->sessionPrefix . '_' . $key, $value);
    }

    /**
     * Get locale.
     *
     * @return mixed
     */
    public function getLocale()
    {
        return $this->getSession('locale', $this->service->config('locale'));
    }

    /**
     * Set locale.
     *
     * @param $locale
     * @return void
     */
    public function setLocale($locale)
    {
        $this->setSession('locale', $locale);
    }

    /**
     * Get domain.
     *
     * @return mixed
     */
    public function getDomain()
    {
        return $this->service->config('domain');
    }
}
