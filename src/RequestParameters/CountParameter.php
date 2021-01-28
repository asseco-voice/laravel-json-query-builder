<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\RequestParameters;

use Asseco\JsonQueryBuilder\Exceptions\JsonQueryBuilderException;
use Illuminate\Support\Facades\DB;

class CountParameter extends AbstractParameter
{
    public static function getParameterName(): string
    {
        return 'count';
    }

    protected function areArgumentsValid(): void
    {
        if (count($this->arguments) != 1) {
            throw new JsonQueryBuilderException("Parameter '{$this->getParameterName()}' expects only one argument.");
        }

        if (!in_array($this->arguments[0], [1, '1', true, 'true'], true)) {
            throw new JsonQueryBuilderException("Parameter '{$this->getParameterName()}' expects to be 'true' if it is to be used.");
        }
    }

    protected function appendQuery(): void
    {
        $this->builder->select(DB::raw('count(*) as count'));
    }
}
