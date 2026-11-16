<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Config;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class ModelConfig
{
    // The version segment invalidates column maps cached before the type names were normalized
    const CACHE_PREFIX = 'table_def_v2_';
    const CACHE_TTL = 86400;

    /**
     * Laravel 11+ returns the database's own type name from getColumnType() (varchar, bool...),
     * Laravel 10 returned the Doctrine DBAL name (string, boolean...). Search behaviour depends on
     * the Doctrine names: a single value on a 'string' column is matched with (I)LIKE, and a
     * 'boolean' column validates its values. Without this map both silently stop on Laravel 11+.
     *
     * Only names that Doctrine mapped to 'string' or 'boolean' are listed, per driver, so a type
     * keeps exactly the behaviour it had on Laravel 10 (e.g. 'text' stays 'text', not 'string').
     * Doctrine names never appear as keys here, so the map is a no-op on Laravel 10.
     */
    const NATIVE_TO_DOCTRINE_TYPES = [
        'pgsql'   => ['varchar' => 'string', 'bpchar' => 'string', 'bool' => 'boolean'],
        'mysql'   => ['varchar' => 'string', 'char' => 'string', 'tinyint' => 'boolean'],
        'mariadb' => ['varchar' => 'string', 'char' => 'string', 'tinyint' => 'boolean'],
        'sqlite'  => ['varchar' => 'string', 'char' => 'string', 'tinyint' => 'boolean'],
    ];

    private Model $model;
    private array $config;

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->config = $this->hasConfig() ? $this->getConfig() : [];
    }

    public function hasConfig(): bool
    {
        return array_key_exists(get_class($this->model), config('asseco-json-query-builder.model_options'));
    }

    protected function getConfig(): array
    {
        return config('asseco-json-query-builder.model_options.' . get_class($this->model));
    }

    public function getReturns(): array
    {
        if (array_key_exists('returns', $this->config) && $this->config['returns']) {
            return Arr::wrap($this->config['returns']);
        }

        return ['*'];
    }

    /**
     * Whether 'returns' may hold aggregations such as "count:*" or "sum:amount". Off unless the
     * model's options set 'aggregations' => true.
     */
    public function allowsAggregations(): bool
    {
        return ($this->config['aggregations'] ?? false) === true;
    }

    public function getRelations(): array
    {
        if (array_key_exists('relations', $this->config) && $this->config['relations']) {
            return Arr::wrap($this->config['relations']);
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
     * Union of Eloquent exclusion (guarded/fillable) and forbidden columns.
     *
     * @param  array  $forbiddenKeys
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
        if (!array_key_exists('eloquent_exclusion', $this->config) || !$this->config['eloquent_exclusion']) {
            return $forbiddenKeys;
        }

        $guarded = $this->model->getGuarded();
        $fillable = $this->model->getFillable();

        if ($guarded[0] != '*') { // Guarded property is never empty. It is '*' by default.
            $forbiddenKeys = array_merge($forbiddenKeys, $guarded);
        } elseif (count($fillable) > 0) {
            $forbiddenKeys = array_diff(array_keys($this->getModelColumns()), $fillable);
        }

        return $forbiddenKeys;
    }

    protected function getForbiddenColumns(array $forbiddenKeys): array
    {
        if (!array_key_exists('forbidden_columns', $this->config) || !$this->config['forbidden_columns']) {
            return $forbiddenKeys;
        }

        return array_merge($forbiddenKeys, $this->config['forbidden_columns']);
    }

    /**
     * Will return column and column type array for a calling model.
     * Column types will equal Eloquent column types.
     *
     * @return array
     */
    public function getModelColumns(): array
    {
        $table = $this->model->getTable();
        $connection = $this->model->getConnection();

        if (Cache::has(self::CACHE_PREFIX . $table)) {
            return Cache::get(self::CACHE_PREFIX . $table);
        }

        $columns = $connection->getSchemaBuilder()->getColumnListing($table);
        $modelColumns = [];

        $this->registerEnumTypeForDoctrine($connection);

        try {
            foreach ($columns as $column) {
                $modelColumns[$column] = self::normalizeColumnType(
                    $connection->getDriverName(),
                    $connection->getSchemaBuilder()->getColumnType($table, $column)
                );
            }
        } catch (Exception) {
            // leave model columns as an empty array and cache it.
        }

        Cache::put(self::CACHE_PREFIX . $table, $modelColumns, self::CACHE_TTL);

        return $modelColumns;
    }

    public static function normalizeColumnType(string $driver, string $type): string
    {
        return self::NATIVE_TO_DOCTRINE_TYPES[$driver][strtolower($type)] ?? $type;
    }

    /**
     * Having 'enum' in table definition will throw Doctrine error because it is not defined in their types.
     * Registering it manually.
     */
    protected function registerEnumTypeForDoctrine($connection): void
    {
        if (!class_exists('Doctrine\DBAL\Driver\AbstractSQLiteDriver')) {
            return;
        }

        $connection
            ->getDoctrineSchemaManager()
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('enum', 'string');
    }
}
