<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\SearchCallbacks;

use Asseco\JsonQueryBuilder\CategorizedValues;
use Exception;
use Illuminate\Database\Eloquent\Builder;

class StartsWith extends AbstractCallback
{
    public static function operator(): string
    {
        return 'starts_with';
    }

    /**
     * @param  Builder  $builder
     * @param  string  $column
     * @param  CategorizedValues  $values
     * @return void
     *
     * @throws Exception
     */
    public function execute(Builder $builder, string $column, CategorizedValues $values): void
    {
        $this->startsWithCallback($builder, $column, $values, 'starts_with');
    }
}
