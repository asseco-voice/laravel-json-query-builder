<?php

namespace Voice\JsonQueryBuilder\Config;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ModelConfig
{
    const CACHE_PREFIX = 'table_def_';
    const CACHE_TTL = 86400;

    private Model $model;
    private array $config;

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->config = $this->hasConfig() ? $this->getConfig() : [];
    }

    public function hasConfig(): bool
    {
        return array_key_exists(get_class($this->model), Config::get('asseco-json-query-builder.model_options'));
    }

    protected function getConfig(): array
    {
        return Config::get('asseco-json-query-builder.model_options.' . get_class($this->model));
    }

    public function getReturns()
    {
        if (array_key_exists('returns', $this->config) && $this->config['returns']) {
            return $this->config['returns'];
        }

        return ['*'];
    }

    public function getRelations()
    {
        if (array_key_exists('relations', $this->config) && $this->config['relations']) {
            return $this->config['relations'];
        }

        return [];
    }

    public function getOrderBy(): array
    {
        $parameters = [];

        if (array_key_exists('order_by', $this->config) && $this->config['order_by']) {
            foreach ($this->config['order_by'] as $key => $value) {
                $parameters[] = "$key=$value";
            }
        }

        return $parameters;
    }

    /**
     * Union of Eloquent exclusion (guarded/fillable) and forbidden columns
     *
     * @param array $forbiddenKeys
     * @return array
     */
    public function getForbidden(array $forbiddenKeys)
    {
        $forbiddenKeys = $this->getEloquentExclusion($forbiddenKeys);
        $forbiddenKeys = $this->getForbiddenColumns($forbiddenKeys);

        return $forbiddenKeys;
    }

    protected function getEloquentExclusion($forbiddenKeys): array
    {
        if (array_key_exists('eloquent_exclusion', $this->config) && $this->config['eloquent_exclusion']) {
            $guarded = $this->model->getGuarded();
            $fillable = $this->model->getFillable();

            if ($guarded[0] != '*') { // Guarded property is never empty. It is '*' by default.
                $forbiddenKeys = array_merge($forbiddenKeys, $guarded);
            } else if (count($fillable) > 0) {
                $forbiddenKeys = array_diff(array_keys($this->getModelColumns()), $fillable);
            }
        }

        return $forbiddenKeys;
    }

    protected function getForbiddenColumns(array $forbiddenKeys): array
    {
        if (array_key_exists('forbidden_columns', $this->config) && $this->config['forbidden_columns']) {
            $forbiddenKeys = array_merge($forbiddenKeys, $this->config['forbidden_columns']);
        }

        return $forbiddenKeys;
    }

    /**
     * Will return column and column type array for a calling model.
     * Column types will equal Eloquent column types
     *
     * @return array
     */
    public function getModelColumns(): array
    {
        $table = $this->model->getTable();

        if (Cache::has(self::CACHE_PREFIX . $table)) {
            return Cache::get(self::CACHE_PREFIX . $table);
        }

        $columns = Schema::getColumnListing($table);
        $modelColumns = [];

        foreach ($columns as $column) {
            $modelColumns[$column] = DB::getSchemaBuilder()->getColumnType($table, $column);
        }

        Cache::put(self::CACHE_PREFIX . $table, $modelColumns, self::CACHE_TTL);

        return $modelColumns;
    }
}
