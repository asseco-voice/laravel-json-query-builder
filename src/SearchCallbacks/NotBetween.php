<?php

namespace Voice\JsonQueryBuilder\SearchCallbacks;

use Illuminate\Database\Eloquent\Builder;
use Voice\JsonQueryBuilder\CategorizedValues;

class NotBetween extends AbstractCallback
{
    const OPERATOR = '!<>';

    public function execute(Builder $builder, string $column, CategorizedValues $values): void
    {
        $this->betweenCallback($builder, $column, $values, '!<>');
    }
}
