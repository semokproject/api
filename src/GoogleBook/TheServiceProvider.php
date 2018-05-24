<?php

namespace Semok\Api\GoogleBook;

use Illuminate\Support\ServiceProvider;

class TheServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/config/api.php' => config_path('semok/api/googlebook.php'),
            ], 'semok.config');
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/api.php', 'semok.api.googlebook');

        // Register the service the package provides.
        $this->app->singleton('semok.api.googlebook', function ($app) {
            return new GoogleBook;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['semok.api.googlebook'];
    }
}
