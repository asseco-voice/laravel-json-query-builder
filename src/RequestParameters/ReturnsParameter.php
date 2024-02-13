<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\RequestParameters;

class ReturnsParameter extends AbstractParameter
{
    public static function getParameterName(): string
    {
        return 'returns';
    }

    protected function appendQuery(): void
    {
        if (!$this->rawArguments) {
            $this->builder->addSelect($this->arguments);
        } else {
            foreach ($this->arguments as $arg) {
                $this->builder->selectRaw($arg);
            }
        }
    }
}
