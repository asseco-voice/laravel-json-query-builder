<?php

namespace Voice\JsonQueryBuilder\RequestParameters;

use Voice\SearchQueryBuilder\Config\OperatorsConfig;
use Voice\SearchQueryBuilder\Exceptions\SearchException;
use Voice\SearchQueryBuilder\RequestParameters\Models\Search;
use Voice\SearchQueryBuilder\SearchCallbacks\AbstractCallback;

class SearchParameter extends AbstractParameter
{
    public function getParameterName(): string
    {
        return 'search';
    }

    public function appendQuery(): void
    {
        $arguments = $this->arguments;
        $operatorsConfig = new OperatorsConfig();

        $this->builder->where(function () use ($arguments, $operatorsConfig) {
            foreach ($arguments as $column => $argument) {
                $searchModel = new Search($this->modelConfig, $operatorsConfig, $column, $argument);
                $this->appendSingle($operatorsConfig, $searchModel);
            }
        });
    }

    /**
     * Append the query based on the given argument
     *
     * @param OperatorsConfig $operatorsConfig
     * @param Search $searchModel
     * @throws SearchException
     */
    protected function appendSingle(OperatorsConfig $operatorsConfig, Search $searchModel): void
    {
        $callbackClassName = $operatorsConfig->getCallbackClassFromOperator($searchModel->operator);

        /**
         * @var AbstractCallback $callback
         */
        new $callbackClassName($this->builder, $searchModel, $operatorsConfig);
    }
}
