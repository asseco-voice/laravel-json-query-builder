<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\RequestParameters;

use Asseco\JsonQueryBuilder\Exceptions\JsonQueryBuilderException;
use Illuminate\Support\Str;

class DoesntHaveRelationsParameter extends AbstractParameter
{
    public static function getParameterName(): string
    {
        return 'doesnt_have_relations';
    }

    protected function appendQuery(): void
    {
        foreach ($this->arguments as $argument) {
            if (is_string($argument)) {
                $this->builder->doesntHave(Str::camel($argument));
                continue;
            }

            throw new JsonQueryBuilderException('Wrong relation parameters provided.');
        }
    }
}
