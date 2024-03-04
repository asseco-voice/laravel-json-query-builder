<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\SearchCallbacks;

use Asseco\JsonQueryBuilder\CategorizedValues;
use Exception;
use Illuminate\Database\Eloquent\Builder;

class EndsWith extends AbstractCallback
{
    public static function operator(): string
    {
        return 'ends_with';
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
        $this->endsWithCallback($builder, $column, $values, 'ends_with');
    }
}
