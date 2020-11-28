<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Config;

use Illuminate\Support\Facades\Config;
use Asseco\JsonQueryBuilder\Exceptions\JsonQueryBuilderException;

abstract class SearchConfig
{
    protected array $config;
    public array    $registered;

    /**
     * SearchConfig constructor.
     * @throws JsonQueryBuilderException
     */
    public function __construct()
    {
        $this->config = config('asseco-json-query-builder');
        $this->register();
    }

    /**
     * Get registered classes from configuration file.
     *
     * @throws JsonQueryBuilderException
     */
    public function register(): void
    {
        $key = static::CONFIG_KEY;
        if (!array_key_exists($key, $this->config)) {
            throw new JsonQueryBuilderException("Config file is missing '$key'");
        }

        $this->registered = $this->config[$key];
    }
}
