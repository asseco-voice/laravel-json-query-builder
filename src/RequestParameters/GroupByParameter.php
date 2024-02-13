<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\RequestParameters;

class GroupByParameter extends AbstractParameter
{
    public static function getParameterName(): string
    {
        return 'group_by';
    }

    protected function appendQuery(): void
    {
        if (!$this->rawArguments) {
            $this->builder->groupBy($this->arguments);
        }
        else {
            foreach($this->arguments as $arg) {
                $this->builder->groupByRaw($arg);
            }
        }
    }
}
