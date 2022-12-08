<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\SearchCallbacks;

use Asseco\JsonQueryBuilder\CategorizedValues;
use Exception;
use Illuminate\Database\Eloquent\Builder;

class Equals extends AbstractCallback
{
    public static function operator(): string
    {
        return '=';
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
        foreach ($values->andLike as $andLike) {
            $builder->where($column, $this->getLikeOperator(), $andLike);
        }

        foreach ($values->notLike as $notLike) {
            $builder->where($column, 'NOT ' . $this->getLikeOperator(), $notLike);
        }

        if ($values->null) {
            $builder->whereNull($column);
        }

        if ($values->notNull) {
            $builder->whereNotNull($column);
        }

        if ($values->and) {
            if ($this->isDate($this->searchParser->type)) {
                foreach ($values->and as $andValue) {
                    $builder->orWhereDate($column, $andValue);
                }
            } else {
                $builder->whereIn($column, $values->and);
            }
        }

        if ($values->not) {
            if ($this->isDate($this->searchParser->type)) {
                throw new Exception('Not operator is not supported for date(time) fields');
            } else {
                $builder->whereNotIn($column, $values->not);
            }
        }
    }
}
