<?php

namespace Voice\JsonQueryBuilder\RequestParameters;

use Illuminate\Support\Str;

class RelationsParameter extends AbstractParameter
{
    public static function getParameterName(): string
    {
        return 'relations';
    }

    public function appendQuery(): void
    {
        foreach ($this->arguments as &$argument) {
            $argument = Str::camel($argument);
        }

        $this->builder->with($this->arguments);
    }
}
