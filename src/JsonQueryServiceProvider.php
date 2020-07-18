<?php

namespace Voice\JsonQueryBuilder;

use Illuminate\Support\ServiceProvider;

class JsonQueryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->publishes([__DIR__ . '/Config/asseco-voice.php' => config_path('asseco-voice.php'),]);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/asseco-voice.php', 'asseco-voice');
    }

}
