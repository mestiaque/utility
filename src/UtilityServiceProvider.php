<?php

namespace ME\Utility;

use Illuminate\Support\ServiceProvider;

class UtilityServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/routes/api.php');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'utility');
        $this->loadTranslationsFrom(__DIR__.'/resources/lang', 'utility');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->publishes([
            __DIR__.'/Config' => config_path('utility'),
        ], 'utility-config');
        $this->publishes([
            __DIR__.'/public' => public_path('vendor/mestiaque/utility'),
        ], 'utility-assets');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/Config/config.php', 'utility');
        
        if (file_exists(__DIR__ . '/Config/sidebar.php')) {
            $this->mergeConfigFrom(__DIR__ . '/Config/sidebar.php', 'sidebar');
        }

        if (file_exists(__DIR__ . '/Config/permissions.php')) {
            $this->mergeConfigFrom(__DIR__ . '/Config/permissions.php', 'permissions');
        }
    }
}
