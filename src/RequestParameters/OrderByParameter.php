<?php

namespace Voice\JsonQueryBuilder\RequestParameters;

class OrderByParameter extends AbstractParameter
{
    public function getParameterName(): string
    {
        return 'order-by';
    }

    public function appendQuery(): void
    {
        $arguments = $this->getArguments();

        foreach ($arguments as $column => $direction) {
            $this->appendSingle($column, $direction);
        }
    }

    protected function appendSingle(string $column, string $direction): void
    {
        $this->builder->orderBy($column, $direction);
    }
}
