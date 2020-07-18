<?php

namespace Voice\JsonQueryBuilder\SearchCallbacks;

use Illuminate\Database\Eloquent\Builder;
use Voice\SearchQueryBuilder\CategorizedValues;

class NotEquals extends AbstractCallback
{
    const OPERATOR = '!=';

    /**
     * Execute a callback on a given column, providing the array of values
     *
     * @param Builder $builder
     * @param string $column
     * @param CategorizedValues $values
     */
    public function execute(Builder $builder, string $column, CategorizedValues $values): void
    {
        foreach (array_merge($values->andLike, $values->notLike) as $like) {
            $builder->where($column, 'NOT LIKE', $like);
        }

        if ($values->null || $values->notNull) {
            $builder->whereNotNull($column);
        }

        if (array_merge($values->and, $values->not)) {
            $builder->whereNotIn($column, array_merge($values->and, $values->not));
        }
    }
}
