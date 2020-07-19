<?php

namespace Voice\JsonQueryBuilder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Voice\JsonQueryBuilder\Config\ModelConfig;
use Voice\JsonQueryBuilder\Config\RequestParametersConfig;
use Voice\JsonQueryBuilder\Exceptions\SearchException;
use Voice\JsonQueryBuilder\RequestParameters\AbstractParameter;

class JsonQuery
{
    protected Builder                 $builder;
    protected array                   $input;
    protected ModelConfig             $modelConfig;
    protected RequestParametersConfig $requestParametersConfig;

    /*
     * TODO: datum od danas toliko dana
     * TODO: or/and?
     */

    /**
     * JsonQuery constructor.
     * @param Builder $builder
     * @param array $input
     * @throws SearchException
     */
    public function __construct(Builder $builder, array $input)
    {
        $this->builder = $builder;
        $this->input = $input;

        if ($this->builder->getModel()->exists) {
            throw new SearchException("[Search] Searching is not allowed on already loaded models.");
        }

        $this->modelConfig = new ModelConfig($builder->getModel());
        $this->requestParametersConfig = new RequestParametersConfig();
    }

    /**
     * Perform the search
     *
     * @throws Exceptions\SearchException
     */
    public function search(): void
    {
        $this->appendParameterQueries();
        $this->appendConfigQueries();
        Log::info('[Search] SQL: ' . $this->builder->toSql() . " Bindings: " . implode(', ', $this->builder->getBindings()));
    }

    /**
     * Append all queries from registered parameters
     *
     * @throws Exceptions\SearchException
     */
    protected function appendParameterQueries(): void
    {
        foreach ($this->requestParametersConfig->registered as $requestParameter) {
            $requestParameter = $this->instantiateRequestParameter($requestParameter);

            if (!($this->parameterExists($requestParameter))) {
                continue;
            }

            $requestParameter->run();
        }
    }

    /**
     * Append all queries from config
     */
    protected function appendConfigQueries(): void
    {
        // TODO: implement...or not
    }

    protected function instantiateRequestParameter($requestParameter): AbstractParameter
    {
        return new $requestParameter($this->input, $this->builder, $this->modelConfig);
    }

    protected function parameterExists(AbstractParameter $requestParameter): bool
    {
        return Arr::has($this->input, $requestParameter->getParameterName());
    }
}
