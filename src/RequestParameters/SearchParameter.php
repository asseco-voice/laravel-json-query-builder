<?php

namespace Voice\JsonQueryBuilder\RequestParameters;

use Illuminate\Database\Eloquent\Builder;
use Voice\JsonQueryBuilder\Config\OperatorsConfig;
use Voice\JsonQueryBuilder\Exceptions\JsonQueryBuilderException;
use Voice\JsonQueryBuilder\Exceptions\SearchException;
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

    public function appendQuery(): void
    {
        $arguments = $this->arguments;
        $operatorsConfig = new OperatorsConfig();

        foreach ($arguments as $column => $argument) {
            $this->applyArguments($operatorsConfig, $column, $argument);
        }
    }

    /**
     * @param OperatorsConfig $operatorsConfig
     * @param string $column
     * @param string $argument
     */
    protected function applyArguments(OperatorsConfig $operatorsConfig, string $column, string $argument): void
    {
        $splitArguments = $this->splitByLogicalOperators($argument);

        foreach ($splitArguments as $splitArgument) {
            $this->builder->orWhere(function ($builder) use ($splitArgument, $operatorsConfig, $column) {
                foreach ($splitArgument as $argument) {
                    $searchModel = new Search($this->modelConfig, $operatorsConfig, $column, $argument);
                    $this->appendSingle($builder, $operatorsConfig, $searchModel);
                }
            });
        }
    }

    /**
     * @param $argument
     * @return array
     * @throws SearchException
     */
    protected function splitByLogicalOperators($argument): array
    {
        $splitByOr = explode(self:: OR, $argument);

        if (empty($splitByOr)) {
            throw new SearchException("Something went wrong. Did you forget to add arguments?");
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
