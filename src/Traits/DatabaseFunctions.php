<?php

namespace Asseco\JsonQueryBuilder\Traits;

use Asseco\JsonQueryBuilder\SQLProviders\PgSQLFunctions;
use Asseco\JsonQueryBuilder\SQLProviders\SQLFunctions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait DatabaseFunctions
{
    public Builder $builder;

    protected array $providers = [
        "pgsql" => PgSQLFunctions::class,
    ];

    protected function areArgumentsValid(): void
    {
        parent::areArgumentsValid();
        foreach ($this->arguments as $argument) {
            SQLFunctions::validateArgument($argument);
        }
    }

    private function applyAggregation(array $params): string
    {
        $column    = array_pop($params);
        $provider  = $this->builder->getModel()->connection ?? config("database.default");
        $functions = $this->providers[$provider] ?? SQLFunctions::class;

        return array_reduce(array_reverse($params), function ($query, $param) use (
            $column,
            $functions
        ) {
            $stat = $query ?? $column;
            return $functions::$param($stat);
        });
    }

    protected function prepareArguments(): void
    {
        $this->arguments = array_map(function ($argument) {
            if (strpos($argument, ":") === false) {
                return $argument;
            }
            $split = explode(":", $argument);
            $apply = $this->applyAggregation($split);
            $alias = join("_", $split);

            return DB::raw("{$apply} as {$alias}");
        }, $this->arguments);
    }
}
