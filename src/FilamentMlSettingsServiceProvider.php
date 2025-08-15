<?php

namespace CodeXpedite\FilamentMlSettings;

use CodeXpedite\FilamentMlSettings\Commands\GenerateSettingsSeederCommand;
use Illuminate\Support\ServiceProvider;

class FilamentMlSettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/filament-ml-settings.php',
            'filament-ml-settings'
        );

        $this->app->singleton('settings', function () {
            return new \CodeXpedite\FilamentMlSettings\Services\SettingsManager();
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'filament-ml-settings');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/filament-ml-settings.php' => config_path('filament-ml-settings.php'),
            ], 'filament-ml-settings-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'filament-ml-settings-migrations');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/filament-ml-settings'),
            ], 'filament-ml-settings-views');

            $this->commands([
                GenerateSettingsSeederCommand::class,
            ]);
        }
    }
}