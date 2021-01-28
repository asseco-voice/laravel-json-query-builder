<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\RequestParameters;

class ReturnsParameter extends AbstractParameter
{
    public static function getParameterName(): string
    {
        return 'returns';
    }

    protected function appendQuery(): void
    {
        $this->builder->select($this->arguments);
    }
}
