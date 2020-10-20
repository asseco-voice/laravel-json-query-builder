<?php

namespace Voice\JsonQueryBuilder;

use Illuminate\Support\ServiceProvider;

class JsonQueryServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/Config/asseco-json-query-builder.php', 'asseco-json-query-builder');
    }

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->publishes([__DIR__.'/Config/asseco-json-query-builder.php' => config_path('asseco-json-query-builder.php')]);
    }
}
