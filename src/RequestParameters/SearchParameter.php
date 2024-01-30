<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\RequestParameters;

use Asseco\JsonQueryBuilder\Config\OperatorsConfig;
use Asseco\JsonQueryBuilder\Exceptions\JsonQueryBuilderException;
use Asseco\JsonQueryBuilder\JsonQuery;
use Asseco\JsonQueryBuilder\SearchCallbacks\AbstractCallback;
use Asseco\JsonQueryBuilder\SearchParser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SearchParameter extends AbstractParameter
{
    const OR = '||';
    const AND = '&&';

    const LARAVEL_WHERE = 'where';
    const LARAVEL_OR_WHERE = 'orWhere';

    protected OperatorsConfig $operatorsConfig;

    public static function getParameterName(): string
    {
        return 'search';
    }

    /**
     * @throws JsonQueryBuilderException
     */
    protected function appendQuery(): void
    {
        $arguments = $this->arguments;

        $this->operatorsConfig = new OperatorsConfig();

        // Wrapped within a where clause to protect from orWhere "exploits".
        $this->builder->where(function (Builder $builder) use ($arguments) {
            $this->makeQuery($builder, $arguments);
        });
    }

    /**
     * Making query from input parameters with recursive calls if needed for top level logical operators (check readme).
     *
     * @param  Builder  $builder
     * @param  array  $arguments
     * @param  string  $boolOperator
     *
     * @throws JsonQueryBuilderException
     */
    protected function makeQuery(Builder $builder, array $arguments, string $boolOperator = self::AND): void
    {
        foreach ($arguments as $key => $value) {
            if ($this->isTopLevelBoolOperator($key)) {
                $this->makeQuery($builder, $value, $key);
                continue;
            }

            $functionName = $this->getQueryFunctionName($boolOperator);

            if ($this->queryInitiatedByTopLevelBool($key, $value)) {
                if (is_array($value)) {
                     // this must be treated together (with AND operator)
                     // [0] => [
                     //   [customFieldValues.date] => <>2024-01-01T00:00:00.000Z;2024-01-31T00:00:00.000Z
                     //   [customFieldValues.custom_field_id] => =96e09c60-6e4f-4ab8-88f1-b21281008ad1
                     // ]
                    $this->makeSingleQueryArr($functionName, $builder, $value);
                }
                else {
                    $builder->{$functionName}(function ($queryBuilder) use ($value) {
                        // Recursion for inner keys which are &&/||
                        $this->makeQuery($queryBuilder, $value);
                    });
                }

                continue;
            }

            if ($this->hasSubSearch($key, $value)) {
                // If query has sub-search, it is a relation for sure.
                $builder->whereHas(Str::camel($key), function ($query) use ($value) {
                    $jsonQuery = new JsonQuery($query, $value);
                    $jsonQuery->search();
                });
                continue;
            }

            $this->makeSingleQuery($functionName, $builder, $key, $value);
        }
    }

    protected function isTopLevelBoolOperator($key): bool
    {
        return in_array($key, [self::OR, self::AND], true);
    }

    /**
     * @param  string  $boolOperator
     * @return string
     *
     * @throws JsonQueryBuilderException
     */
    protected function getQueryFunctionName(string $boolOperator): string
    {
        if ($boolOperator === self::AND) {
            return self::LARAVEL_WHERE;
        } elseif ($boolOperator === self::OR) {
            return self::LARAVEL_OR_WHERE;
        }

        throw new JsonQueryBuilderException('Invalid bool operator provided');
    }

    protected function queryInitiatedByTopLevelBool($key, $value): bool
    {
        // Since this will be triggered by recursion, key will be numeric
        // and not the actual key.
        return !is_string($key) && is_array($value);
    }

    protected function hasSubSearch($key, $value): bool
    {
        return is_string($key) && is_array($value);
    }

    /**
     * @param  string  $functionName
     * @param  Builder  $builder
     * @param  $key
     * @param  $value
     *
     * @throws JsonQueryBuilderException
     */
    protected function makeSingleQuery(string $functionName, Builder $builder, $key, $value): void
    {
        $builder->{$functionName}(function ($queryBuilder) use ($key, $value) {
            $this->applyArguments($queryBuilder, $this->operatorsConfig, $key, $value);
        });
    }

    /**
     * @param string $functionName
     * @param Builder $builder
     * @param $values
     * @return void
     * @throws JsonQueryBuilderException
     */
    protected function makeSingleQueryArr(string $functionName, Builder $builder, $values): void
    {
        $builder->{$functionName}(function ($queryBuilder) use ($values, $functionName) {
            foreach($values as $key => $value) {
                if (is_array($value)) {
                    $this->makeSingleQueryArr($functionName, $queryBuilder, $value);
                }
                else {
                    $this->applyArguments($queryBuilder, $this->operatorsConfig, $key, $value, 'AND');
                }
            }
        });
    }

    /**
     * @param  Builder  $builder
     * @param  OperatorsConfig  $operatorsConfig
     * @param  string  $column
     * @param  string  $argument
     * @param  string  $orAnd
     *
     * @throws JsonQueryBuilderException
     */
    protected function applyArguments(Builder $builder, OperatorsConfig $operatorsConfig, string $column, string $argument, ?string $orAnd = 'OR'): void
    {
        $fn = strtoupper($orAnd) == 'AND' ? 'where' : 'orWhere';
        $splitArguments = $this->splitByBoolOperators($argument);

        foreach ($splitArguments as $splitArgument) {
            $builder->{$fn}(function ($builder) use ($splitArgument, $operatorsConfig, $column) {
                foreach ($splitArgument as $argument) {
                    $searchModel = new SearchParser($this->modelConfig, $operatorsConfig, $column, $argument);

                    $this->appendSingle($builder, $operatorsConfig, $searchModel);
                }
            });
        }
    }

    /**
     * @param  $argument
     * @return array
     *
     * @throws JsonQueryBuilderException
     */
    protected function splitByBoolOperators($argument): array
    {
        $splitByOr = explode(self::OR, $argument);

        if (empty($splitByOr)) {
            throw new JsonQueryBuilderException('Something went wrong. Did you forget to add arguments?');
        }

        $splitByAnd = [];

        foreach ($splitByOr as $item) {
            $splitByAnd[] = explode(self::AND, $item);
        }

        return $splitByAnd;
    }

    /**
     * Append the query based on the given argument.
     *
     * @param  Builder  $builder
     * @param  OperatorsConfig  $operatorsConfig
     * @param  SearchParser  $searchParser
     *
     * @throws JsonQueryBuilderException
     */
    protected function appendSingle(Builder $builder, OperatorsConfig $operatorsConfig, SearchParser $searchParser): void
    {
        $callbackClassName = $operatorsConfig->getCallbackClassFromOperator($searchParser->operator);

        /** @var AbstractCallback $callback */
        new $callbackClassName($builder, $searchParser);
    }
}
