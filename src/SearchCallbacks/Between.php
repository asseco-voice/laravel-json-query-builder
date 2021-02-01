<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\SearchCallbacks;

use Asseco\JsonQueryBuilder\CategorizedValues;
use Illuminate\Database\Eloquent\Builder;

class Between extends AbstractCallback
{
    public static function operator(): string
    {
        return '<>';
    }

    public function execute(Builder $builder, string $column, CategorizedValues $values): void
    {
        $this->betweenCallback($builder, $column, $values, '<>');
    }
}
