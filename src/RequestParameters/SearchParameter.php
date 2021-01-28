<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\RequestParameters;

use Asseco\JsonQueryBuilder\Config\OperatorsConfig;
use Asseco\JsonQueryBuilder\Exceptions\JsonQueryBuilderException;
use Asseco\JsonQueryBuilder\SearchParser;
use Asseco\JsonQueryBuilder\SearchCallbacks\AbstractCallback;
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
     * @param Builder $builder
     * @param array $arguments
     * @param string $boolOperator
     * @throws JsonQueryBuilderException
     */
    protected function makeQuery(Builder $builder, array $arguments, string $boolOperator = self:: AND): void
    {
        foreach ($arguments as $key => $value) {
            $key = $this->forceCamelCaseOnRelationKeys($key);

            if ($this->isBoolOperator($key)) {
                // Recursion for keys which are &&/||
                $this->makeQuery($builder, $value, $key);
                continue;
            }

            $functionName = $this->getQueryFunctionName($boolOperator);

            if (!$this->shouldSplitQueries($value)) {
                $this->makeSingleQuery($functionName, $builder, $key, $value);
                continue;
            }

            $builder->{$functionName}(function ($queryBuilder) use ($value) {
                // Recursion for inner keys which are &&/||
                $this->makeQuery($queryBuilder, $value);
            });
        }
    }

    protected function forceCamelCaseOnRelationKeys($key)
    {
        if (!is_string($key)) {
            return $key;
        }

        $exploded = explode('.', $key);

        if (count($exploded) < 2) {
            return $key;
        }

        return implode('.', [Str::camel($exploded[0]), $exploded[1]]);
    }

    protected function isBoolOperator($key): bool
    {
        return in_array($key, [self:: OR, self:: AND], true);
    }

    /**
     * @param string $boolOperator
     * @return string
     * @throws JsonQueryBuilderException
     */
    protected function getQueryFunctionName(string $boolOperator): string
    {
        if ($boolOperator === self:: AND) {
            return self::LARAVEL_WHERE;
        } elseif ($boolOperator === self:: OR) {
            return self::LARAVEL_OR_WHERE;
        }

        throw new JsonQueryBuilderException('Invalid bool operator provided');
    }

    protected function shouldSplitQueries($value): bool
    {
        return is_array($value);
    }

    /**
     * @param string $functionName
     * @param Builder $builder
     * @param $key
     * @param $value
     * @throws JsonQueryBuilderException
     */
    protected function makeSingleQuery(string $functionName, Builder $builder, $key, $value): void
    {
        $builder->{$functionName}(function ($queryBuilder) use ($key, $value) {
            $this->applyArguments($queryBuilder, $this->operatorsConfig, $key, $value);
        });
    }

    /**
     * @param Builder $builder
     * @param OperatorsConfig $operatorsConfig
     * @param string $column
     * @param string $argument
     * @throws JsonQueryBuilderException
     */
    protected function applyArguments(Builder $builder, OperatorsConfig $operatorsConfig, string $column, string $argument): void
    {
        $splitArguments = $this->splitByBoolOperators($argument);

        foreach ($splitArguments as $splitArgument) {
            $builder->orWhere(function ($builder) use ($splitArgument, $operatorsConfig, $column) {
                foreach ($splitArgument as $argument) {
                    $searchModel = new SearchParser($this->modelConfig, $operatorsConfig, $column, $argument);
                    $this->appendSingle($builder, $operatorsConfig, $searchModel);
                }
            });
        }
    }

    /**
     * @param $argument
     * @return array
     * @throws JsonQueryBuilderException
     */
    protected function splitByBoolOperators($argument): array
    {
        $splitByOr = explode(self:: OR, $argument);

        if (empty($splitByOr)) {
            throw new JsonQueryBuilderException('Something went wrong. Did you forget to add arguments?');
        }

        $splitByAnd = [];

        foreach ($splitByOr as $item) {
            $splitByAnd[] = explode(self:: AND, $item);
        }

        return $splitByAnd;
    }

    /**
     * Append the query based on the given argument.
     *
     * @param Builder $builder
     * @param OperatorsConfig $operatorsConfig
     * @param SearchParser $searchParser
     * @throws JsonQueryBuilderException
     */
    protected function appendSingle(Builder $builder, OperatorsConfig $operatorsConfig, SearchParser $searchParser): void
    {
        $callbackClassName = $operatorsConfig->getCallbackClassFromOperator($searchParser->operator);

        /**
         * @var AbstractCallback $callback
         */
        new $callbackClassName($builder, $searchParser);
    }
}
