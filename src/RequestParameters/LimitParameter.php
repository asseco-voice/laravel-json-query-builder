<?php

namespace Voice\JsonQueryBuilder\RequestParameters;

use Voice\JsonQueryBuilder\Exceptions\JsonQueryBuilderException;

class LimitParameter extends AbstractParameter
{
    public static function getParameterName(): string
    {
        return 'limit';
    }

    public function areArgumentsValid(): void
    {
        if (count($this->arguments) != 1) {
            throw new JsonQueryBuilderException("Parameter '{$this->getParameterName()}' expects only one argument.");
        }
    }

    public function appendQuery(): void
    {
        $this->builder->limit($this->arguments[0]);
    }
}
