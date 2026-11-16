<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\RequestParameters;

use Asseco\JsonQueryBuilder\Aggregations\AggregateExpression;
use Illuminate\Support\Facades\Config;

class ReturnsParameter extends AbstractParameter
{
    public static function getParameterName(): string
    {
        return 'returns';
    }

    protected function appendQuery(): void
    {
        // Without an aggregation in the request, or without the model's opt-in, the arguments are
        // passed on exactly as before aggregations existed
        $hasAggregates = (bool) array_filter(
            $this->arguments,
            fn ($argument) => AggregateExpression::isAggregate((string) $argument)
        );

        if (!$hasAggregates || !$this->modelConfig->allowsAggregations()) {
            $this->builder->addSelect($this->arguments);

            return;
        }

        $this->builder->addSelect(array_map(function ($argument) {
            if (!AggregateExpression::isAggregate((string) $argument)) {
                return $argument;
            }

            $query = $this->builder->getQuery();

            return $query->raw(AggregateExpression::toSql(
                $argument,
                $this->aggregatableColumns(),
                $query->getConnection()->getDriverName(),
                fn ($identifier) => $query->getGrammar()->wrap($identifier),
            ));
        }, $this->arguments));
    }

    /**
     * Model columns minus the globally and per-model forbidden ones.
     */
    protected function aggregatableColumns(): array
    {
        $forbidden = $this->modelConfig->getForbidden(
            Config::get('asseco-json-query-builder.global_forbidden_columns', [])
        );

        return array_values(array_diff(array_keys($this->modelConfig->getModelColumns()), $forbidden));
    }
}
