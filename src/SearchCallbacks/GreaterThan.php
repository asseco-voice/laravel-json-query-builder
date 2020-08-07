<?php

namespace Voice\JsonQueryBuilder\SearchCallbacks;

use Illuminate\Database\Eloquent\Builder;
use Voice\JsonQueryBuilder\CategorizedValues;

class GreaterThan extends AbstractCallback
{
    const OPERATOR = '>';

    public function execute(Builder $builder, string $column, CategorizedValues $values): void
    {
        $this->lessOrMoreCallback($builder, $column, $values, '>');
    }
}
