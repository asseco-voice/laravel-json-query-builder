<?php

namespace Voice\JsonQueryBuilder\RequestParameters;

use Voice\JsonQueryBuilder\Exceptions\SearchException;

class OffsetParameter extends AbstractParameter
{
    public function getParameterName(): string
    {
        return 'offset';
    }

    public function areArgumentsValid(): void
    {
        if (count($this->arguments) != 1) {
            throw new SearchException("[Search] Parameter '{$this->getParameterName()}' expects only one argument.");
        }
    }

    public function appendQuery(): void
    {
        $this->builder->offset($this->arguments[0]);
    }
}
