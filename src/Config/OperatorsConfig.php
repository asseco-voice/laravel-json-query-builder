<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Config;

use Asseco\JsonQueryBuilder\Exceptions\JsonQueryBuilderException;
use Asseco\JsonQueryBuilder\SearchCallbacks\AbstractCallback;

class OperatorsConfig extends SearchConfig
{
    const CONFIG_KEY = 'operators';

    protected function operatorCallbackMapping(): array
    {
        $operators = $this->getOperators();
        $callbacks = $this->registered;

        return array_combine($operators, $callbacks);
    }

    /**
     * Extract operators from registered 'operator' classes
     * @return array
     */
    public function getOperators(): array
    {
        /**
         * @var AbstractCallback $callback
         */
        return array_map(fn ($callback) => $callback::getCallbackOperator(), $this->registered);
    }

    /**
     * @param string $operator
     * @return string
     * @throws JsonQueryBuilderException
     */
    public function getCallbackClassFromOperator(string $operator): string
    {
        if (!array_key_exists($operator, $this->operatorCallbackMapping())) {
            throw new JsonQueryBuilderException("No valid callback registered for '$operator' operator.");
        }

        return $this->operatorCallbackMapping()[$operator];
    }
}
