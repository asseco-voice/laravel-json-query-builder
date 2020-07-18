<?php

namespace Voice\JsonQueryBuilder\SearchCallbacks;

use Illuminate\Database\Eloquent\Builder;
use Voice\SearchQueryBuilder\CategorizedValues;

class LessThanOrEqual extends AbstractCallback
{
    const OPERATOR = '<=';

    /**
     * Execute a callback on a given column, providing the array of values
     *
     * @param Builder $builder
     * @param string $column
     * @param CategorizedValues $values
     * @throws \Voice\SearchQueryBuilder\Exceptions\SearchException
     */
    public function execute(Builder $builder, string $column, CategorizedValues $values): void
    {
        $this->lessOrMoreCallback($builder, $column, $values, '<=');
    }
}
