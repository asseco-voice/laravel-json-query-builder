<?php

declare (strict_types = 1);

namespace Asseco\JsonQueryBuilder\RequestParameters;

use Asseco\JsonQueryBuilder\Traits\DatabaseFunctions;

class ReturnsParameter extends AbstractParameter
{
    use DatabaseFunctions;

    public static function getParameterName(): string
    {
        return 'returns';
    }

    protected function appendQuery(): void
    {
        $this->prepareArguments();
        $this->builder->addSelect($this->arguments);
    }
}
