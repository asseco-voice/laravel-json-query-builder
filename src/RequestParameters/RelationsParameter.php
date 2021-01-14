<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\RequestParameters;

use Asseco\JsonQueryBuilder\Exceptions\JsonQueryBuilderException;
use Asseco\JsonQueryBuilder\JsonQuery;
use Illuminate\Support\Str;

class RelationsParameter extends AbstractParameter
{
    public static function getParameterName(): string
    {
        return 'relations';
    }

    public function appendQuery(): void
    {
        foreach ($this->arguments as $argument) {
            if (is_string($argument)) {
                $this->appendSimpleRelation($argument);
                continue;
            }

            if (is_array($argument) && count($argument) > 0) {
                $this->appendComplexRelation($argument);
                continue;
            }

            throw new JsonQueryBuilderException('Wrong relation parameters provided.');
        }
    }

    protected function appendSimpleRelation(string $argument): void
    {
        $this->builder->with(Str::camel($argument));
    }

    protected function appendComplexRelation(array $argument): void
    {
        $relation = key($argument);
        $input = $argument[$relation];

        $this->builder->with([Str::camel($relation) => function ($query) use ($input) {
            $jsonQuery = new JsonQuery($query->getQuery(), $input);
            $jsonQuery->search();
        }]);
    }
}
