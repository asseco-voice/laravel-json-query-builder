<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder;

use Illuminate\Support\ServiceProvider;

class JsonQueryServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/asseco-json-query-builder.php', 'asseco-json-query-builder');
    }

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->publishes([__DIR__ . '/../config/asseco-json-query-builder.php' => config_path('asseco-json-query-builder.php')]);
    }
}
