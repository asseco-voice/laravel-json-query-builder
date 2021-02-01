<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\SearchCallbacks;

use Asseco\JsonQueryBuilder\CategorizedValues;
use Illuminate\Database\Eloquent\Builder;

class NotEquals extends AbstractCallback
{
    public static function operator(): string
    {
        return '!=';
    }

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
