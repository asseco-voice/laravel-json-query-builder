<?php

namespace Voice\JsonQueryBuilder\Config;

use Voice\SearchQueryBuilder\Exceptions\SearchException;
use Voice\SearchQueryBuilder\SearchCallbacks\AbstractCallback;

class OperatorsConfig extends SearchConfig
{
    const CONFIG_KEY = 'operators';

    protected function operatorCallbackMapping(): array
    {
        $operators = $this->getOperators();
        $callbacks = $this->registered;

        return array_combine($operators, $callbacks);
    }

    public function getOperators()
    {
        /**
         * @var AbstractCallback $callback
         */
        return array_map(fn($callback) => $callback::getCallbackOperator(), $this->registered);
    }

    /**
     * @param string $operator
     * @return string
     * @throws SearchException
     */
    public function getCallbackClassFromOperator(string $operator): string
    {
        if (!array_key_exists($operator, $this->operatorCallbackMapping())) {
            throw new SearchException("[Search] No valid callback registered for '$operator' operator.");
        }

        return $this->operatorCallbackMapping()[$operator];
    }
}
