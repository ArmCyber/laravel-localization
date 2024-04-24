<?php

namespace ArmCyber\Localization\Providers;

use ArmCyber\Localization\Commands\RemoveEmptyTermsCommand;
use ArmCyber\Localization\Commands\ExportTranslationsCommand;
use ArmCyber\Localization\Commands\ImportTranslationsCommand;
use ArmCyber\Localization\Services\LocalizationService;
use ArmCyber\Localization\Services\PoEditorApiService;
use ArmCyber\Localization\Services\TranslationService;
use Illuminate\Support\ServiceProvider;

class LocalizationServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerServices();
        $this->registerConfig();
    }

    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPublishes();
        $this->commands([
            ExportTranslationsCommand::class,
            ImportTranslationsCommand::class,
            RemoveEmptyTermsCommand::class
        ]);
    }

    /**
     * Register services.
     *
     * @return void
     */
    private function registerServices()
    {
        $this->app->singleton(LocalizationService::class);
        $this->app->singleton(PoEditorApiService::class);
        $this->app->singleton(TranslationService::class);
        $this->app->alias(LocalizationService::class, 'localization');
    }

    /**
     * Register config.
     *
     * @return void
     */
    private function registerConfig()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/localization.php', 'localization');
    }

    /**
     * Register publishes.
     *
     * @return void
     */
    private function registerPublishes()
    {
        $this->publishes([
            __DIR__ . '/../../config/localization.php' => config_path('localization.php'),
        ], 'armcyber-localization');
    }
}
