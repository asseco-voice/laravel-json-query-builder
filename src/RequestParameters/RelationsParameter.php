<?php

namespace Voice\JsonQueryBuilder\RequestParameters;

class RelationsParameter extends AbstractParameter
{
    public function getParameterName(): string
    {
        return 'relations';
    }

    public function appendQuery(): void
    {
        $this->builder->with($this->arguments);
    }
}
