<?php

namespace Voice\JsonQueryBuilder\RequestParameters;

class OrderByParameter extends AbstractParameter
{
    public static function getParameterName(): string
    {
        return 'order_by';
    }

    public function appendQuery(): void
    {
        foreach ($this->arguments as $column => $direction) {
            $this->appendSingle($column, $direction);
        }
    }

    protected function appendSingle(string $column, string $direction): void
    {
        $this->builder->orderBy($column, $direction);
    }
}
