<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\RequestParameters;

class OrderByParameter extends AbstractParameter
{
    public static function getParameterName(): string
    {
        return 'order_by';
    }

    protected function appendQuery(): void
    {
        foreach ($this->arguments as $column => $direction) {

            [$column, $direction] = $this->fallBackToDefaultDirection($column, $direction);

            $this->appendSingle($column, $direction);
        }
    }

    /**
     * If argument is provided as a simple string without direction, we will
     * assume that direction is 'asc'
     *
     * @param string|int $column
     * @param string $direction
     * @return array
     */
    protected function fallBackToDefaultDirection($column, string $direction): array
    {
        if (is_numeric($column)) {
            $column = $direction;
            $direction = 'asc';
        }

        return [$column, $direction];
    }

    protected function appendSingle(string $column, string $direction): void
    {
        $this->builder->orderBy($column, $direction);
    }
}
