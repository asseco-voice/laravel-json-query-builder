<?php

declare(strict_types=1);

namespace Voice\JsonQueryBuilder\RequestParameters;

use Voice\JsonQueryBuilder\Exceptions\JsonQueryBuilderException;

class OffsetParameter extends AbstractParameter
{
    public static function getParameterName(): string
    {
        return 'offset';
    }

    public function areArgumentsValid(): void
    {
        if (count($this->arguments) != 1) {
            throw new JsonQueryBuilderException("Parameter '{$this->getParameterName()}' expects only one argument.");
        }
    }

    public function appendQuery(): void
    {
        $this->builder->offset($this->arguments[0]);
    }
}
