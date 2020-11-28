<?php

namespace Asseco\JsonQueryBuilder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Asseco\JsonQueryBuilder\Config\ModelConfig;
use Asseco\JsonQueryBuilder\Config\RequestParametersConfig;
use Asseco\JsonQueryBuilder\Exceptions\JsonQueryBuilderException;
use Asseco\JsonQueryBuilder\RequestParameters\AbstractParameter;

class JsonQuery
{
    protected Builder     $builder;
    protected array       $input;
    protected ModelConfig $modelConfig;
    protected array       $registeredParameters;

    /**
     * JsonQuery constructor.
     * @param Builder $builder
     * @param array $input
     * @throws JsonQueryBuilderException
     */
    public function __construct(Builder $builder, array $input)
    {
        $this->builder = $builder;
        $this->input = $input;

        $this->forbidForExistingModels();

        $this->modelConfig = new ModelConfig($this->builder->getModel());
        $this->registeredParameters = (new RequestParametersConfig())->registered;
    }

    /**
     * @throws JsonQueryBuilderException
     */
    protected function forbidForExistingModels(): void
    {
        if ($this->builder->getModel()->exists) {
            throw new JsonQueryBuilderException('Searching is not allowed on already loaded models.');
        }
    }

    /**
     * Perform the search.
     *
     * @throws Exceptions\JsonQueryBuilderException
     */
    public function search(): void
    {
        $this->appendParameterQueries();
        $this->appendConfigQueries();
    }

    /**
     * Append all queries from registered parameters.
     *
     * @throws Exceptions\JsonQueryBuilderException
     */
    protected function appendParameterQueries(): void
    {
        foreach ($this->registeredParameters as $requestParameter) {
            if (!$this->parameterExists($requestParameter)) {
                // TODO: append config query?
                continue;
            }

            $this->instantiateRequestParameter($requestParameter)
                ->run();
        }
    }

    /**
     * Append all queries from config.
     */
    protected function appendConfigQueries(): void
    {
        // TODO: implement...or not
    }

    /**
     * @param string $requestParameter
     * @return bool
     */
    protected function parameterExists(string $requestParameter): bool
    {
        /**
         * @var AbstractParameter $requestParameter
         */
        return Arr::has($this->input, $requestParameter::getParameterName());
    }

    /**
     * @param $requestParameter
     * @return AbstractParameter
     */
    protected function instantiateRequestParameter($requestParameter): AbstractParameter
    {
        /**
         * @var AbstractParameter $requestParameter
         */
        $input = $this->wrapInput($requestParameter::getParameterName());

        return new $requestParameter($input, $this->builder, $this->modelConfig);
    }

    /**
     * Get input for given parameter name and wrap it as an array if it's not already an array.
     *
     * @param string $parameterName
     * @return array
     */
    protected function wrapInput(string $parameterName): array
    {
        return Arr::wrap(
            Arr::get($this->input, $parameterName)
        );
    }
}
