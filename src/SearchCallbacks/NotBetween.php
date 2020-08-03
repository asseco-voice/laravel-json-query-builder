<?php

namespace Voice\JsonQueryBuilder\SearchCallbacks;

use Illuminate\Database\Eloquent\Builder;
use Voice\JsonQueryBuilder\CategorizedValues;
use Voice\JsonQueryBuilder\Exceptions\JsonQueryBuilderException;

class NotBetween extends AbstractCallback
{
    const OPERATOR = '!<>';

    /**
     * Execute a callback on a given column, providing the array of values
     *
     * @param Builder $builder
     * @param string $column
     * @param CategorizedValues $values
     * @throws JsonQueryBuilderException
     */
    public function execute(Builder $builder, string $column, CategorizedValues $values): void
    {
        $this->betweenCallback($builder, $column, $values, '!<>');
    }
}
