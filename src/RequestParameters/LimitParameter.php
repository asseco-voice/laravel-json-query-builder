<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\RequestParameters;

use Asseco\JsonQueryBuilder\Exceptions\JsonQueryBuilderException;

class LimitParameter extends AbstractParameter
{
    public static function getParameterName(): string
    {
        return 'limit';
    }

    protected function areArgumentsValid(): void
    {
        if (count($this->arguments) != 1) {
            throw new JsonQueryBuilderException("Parameter '{$this->getParameterName()}' expects only one argument.");
        }

        if (!is_numeric($this->arguments[0])) {
            throw new JsonQueryBuilderException("Parameter '{$this->getParameterName()}' must be numeric.");
        }
    }

    protected function appendQuery(): void
    {
        $this->builder->limit($this->arguments[0]);
    }
}
