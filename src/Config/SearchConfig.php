<?php

namespace Voice\JsonQueryBuilder\Config;

use Illuminate\Support\Facades\Config;
use Voice\JsonQueryBuilder\Exceptions\SearchException;

abstract class SearchConfig
{
    protected array $config;
    public array    $registered;

    /**
     * SearchConfig constructor.
     * @throws SearchException
     */
    public function __construct()
    {
        $this->config = Config::get('asseco-json-query-builder');
        $this->register();
    }

    /**
     * Get registered classes from configuration file
     *
     * @throws SearchException
     */
    public function register(): void
    {
        $key = static::CONFIG_KEY;
        if (!array_key_exists($key, $this->config)) {
            throw new SearchException("[Search] Config file is missing '$key'");
        }

        $this->registered = $this->config[$key];
    }
}
