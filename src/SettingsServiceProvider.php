<?php

namespace Poisa\Settings;

use Illuminate\Support\ServiceProvider;
use Poisa\Settings\Serializers\ScalarInteger;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $configFile = realpath(__DIR__ . '/../config/settings.php');
        $migrationPath = realpath(__DIR__ . '/../migrations/');

        $this->publishes([$configFile => config_path('settings.php')]);
        $this->mergeConfigFrom($configFile, 'settings');
        $this->loadMigrationsFrom($migrationPath);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Settings::class, function ($app) {
            $settings = new Settings;
            return $settings;
        });
    }
}
