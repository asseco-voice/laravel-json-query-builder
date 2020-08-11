<?php

namespace Voice\JsonQueryBuilder\RequestParameters;

use Illuminate\Database\Eloquent\Builder;
use Voice\JsonQueryBuilder\Config\OperatorsConfig;
use Voice\JsonQueryBuilder\Exceptions\JsonQueryBuilderException;
use Voice\JsonQueryBuilder\RequestParameters\Models\Search;
use Voice\JsonQueryBuilder\SearchCallbacks\AbstractCallback;

class SearchParameter extends AbstractParameter
{
    const OR = '||';
    const AND = '&&';

    public static function getParameterName(): string
    {
        return 'search';
    }

    /**
     * @throws JsonQueryBuilderException
     */
    public function appendQuery(): void
    {
        $arguments = $this->arguments;
        $operatorsConfig = new OperatorsConfig();

        $this->makeQuery($this->builder, $operatorsConfig, $arguments);
    }

    /**
     * Let's hope this doesn't need to be debugged...ever...
     * @param Builder $builder
     * @param OperatorsConfig $operatorsConfig
     * @param array $arguments
     * @param string $logicalOperator
     * @throws JsonQueryBuilderException
     */
    protected function makeQuery(Builder $builder, OperatorsConfig $operatorsConfig, array $arguments, string $logicalOperator = self:: AND): void
    {
        foreach ($arguments as $key => $value) {

            // Recursive call for cases when key is &&/||
            if (in_array($key, [self:: OR, self:: AND], true)) {
                $this->makeQuery($builder, $operatorsConfig, $value, $key);
                continue;
            }

            $functionName = $this->getQueryFunctionName($logicalOperator);


            if (is_array($value)) {
                // Wrap inner queries to where/orWhere
                $builder->{$functionName}(function ($queryBuilder) use ($operatorsConfig, $value, $key, $logicalOperator) {
                    foreach ($value as $column => $arguments) {
                        if (is_array($arguments)) {
                            // Additional recursion for inner keys which are &&/||
                            $this->makeQuery($queryBuilder, $operatorsConfig, [$column => $arguments]);
                        } else {
                            // Wrap inner arguments
                            $queryBuilder->where(function ($innerQueryBuilder) use ($operatorsConfig, $column, $arguments) {
                                $this->applyArguments($innerQueryBuilder, $operatorsConfig, $column, $arguments);
                            });
                        }
                    }
                });

                continue;
            }

            $builder->{$functionName}(function ($queryBuilder) use ($operatorsConfig, $key, $value) {
                $this->applyArguments($queryBuilder, $operatorsConfig, $key, $value);
            });
        }
    }

    /**
     * @param OperatorsConfig $operatorsConfig
     * @param string $column
     * @param string $argument
     * @throws JsonQueryBuilderException
     */
    protected function applyArguments(Builder $builder, OperatorsConfig $operatorsConfig, string $column, string $argument): void
    {
        $splitArguments = $this->splitByLogicalOperators($argument);

        foreach ($splitArguments as $splitArgument) {
            $builder->orWhere(function ($builder) use ($splitArgument, $operatorsConfig, $column) {
                foreach ($splitArgument as $argument) {
                    $searchModel = new Search($this->modelConfig, $operatorsConfig, $column, $argument);
                    $this->appendSingle($builder, $operatorsConfig, $searchModel);
                }
            });
        }
    }

    /**
     * @param string $logicalOperator
     * @return string
     * @throws JsonQueryBuilderException
     */
    protected function getQueryFunctionName(string $logicalOperator): string
    {
        if ($logicalOperator === self:: AND) {
            $queryFunction = 'where';
        } elseif ($logicalOperator === self:: OR) {
            $queryFunction = 'orWhere';
        } else {
            throw new JsonQueryBuilderException("Invalid logical operator provided");
        }

        return $queryFunction;
    }

    /**
     * @param $argument
     * @return array
     * @throws JsonQueryBuilderException
     */
    protected function splitByLogicalOperators($argument): array
    {
        $splitByOr = explode(self:: OR, $argument);

        if (empty($splitByOr)) {
            throw new JsonQueryBuilderException("Something went wrong. Did you forget to add arguments?");
        }

        $splitByAnd = [];

        foreach ($splitByOr as $item) {
            $splitByAnd[] = explode(self:: AND, $item);
        }

        return $splitByAnd;
    }

    /**
     * Append the query based on the given argument
     *
     * @param Builder $builder
     * @param OperatorsConfig $operatorsConfig
     * @param Search $searchModel
     * @throws JsonQueryBuilderException
     */
    protected function appendSingle(Builder $builder, OperatorsConfig $operatorsConfig, Search $searchModel): void
    {
        $callbackClassName = $operatorsConfig->getCallbackClassFromOperator($searchModel->operator);

        /**
         * @var AbstractCallback $callback
         */
        new $callbackClassName($builder, $searchModel, $operatorsConfig);
    }
}
