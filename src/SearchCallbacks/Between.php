<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\SearchCallbacks;

use Illuminate\Database\Eloquent\Builder;
use Asseco\JsonQueryBuilder\CategorizedValues;

class Between extends AbstractCallback
{
    const OPERATOR = '<>';

    public function execute(Builder $builder, string $column, CategorizedValues $values): void
    {
        $this->betweenCallback($builder, $column, $values, '<>');
    }
}
