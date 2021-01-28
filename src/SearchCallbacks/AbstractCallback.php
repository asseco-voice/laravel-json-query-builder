<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\SearchCallbacks;

use Asseco\JsonQueryBuilder\CategorizedValues;
use Asseco\JsonQueryBuilder\Exceptions\JsonQueryBuilderException;
use Asseco\JsonQueryBuilder\SearchParser;
use Illuminate\Database\Eloquent\Builder;

abstract class AbstractCallback
{
    protected Builder           $builder;
    protected SearchParser      $searchParser;
    protected CategorizedValues $categorizedValues;

    /**
     * AbstractCallback constructor.
     * @param Builder $builder
     * @param SearchParser $searchParser
     * @throws JsonQueryBuilderException
     */
    public function __construct(Builder $builder, SearchParser $searchParser)
    {
        $this->builder = $builder;
        $this->searchParser = $searchParser;

        $this->categorizedValues = new CategorizedValues($this->searchParser);

        $this->builder->when(
            str_contains($this->searchParser->column, '.'),
            function (Builder $builder) {
                $this->appendRelations($builder, $this->searchParser->column, $this->categorizedValues);
            },
            function (Builder $builder) {
                $this->execute($builder, $this->searchParser->column, $this->categorizedValues);
            }
        );
    }

    /**
     * Child class MUST extend a NAME constant.
     * This is a Laravel friendly name for columns based on Laravel migration column types.
     *
     * @return string
     */
    public static function getCallbackOperator(): string
    {
        return static::OPERATOR;
    }

    /**
     * Execute a callback on a given column, providing the array of values.
     * @param Builder $builder
     * @param string $column
     * @param CategorizedValues $values
     * @throws JsonQueryBuilderException
     */
    abstract public function execute(Builder $builder, string $column, CategorizedValues $values): void;

    protected function appendRelations(Builder $builder, string $column, CategorizedValues $values): void
    {
        [$relationName, $relatedColumn] = explode('.', $column);

        $builder->orWhereHas($relationName, function (Builder $builder) use ($relatedColumn, $values) {
            $this->execute($builder, $relatedColumn, $values);
        });
    }

    /**
     * @param Builder $builder
     * @param string $column
     * @param CategorizedValues $values
     * @param string $operator
     * @throws JsonQueryBuilderException
     */
    protected function lessOrMoreCallback(Builder $builder, string $column, CategorizedValues $values, string $operator)
    {
        $this->checkAllowedValues($values, $operator);

        if (count($values->and) > 1) {
            throw new JsonQueryBuilderException("Using $operator operator assumes one parameter only. Remove excess parameters.");
        }

        if (!$values->and) {
            throw new JsonQueryBuilderException("No valid arguments for '$operator' operator.");
        }

        $builder->where($column, $operator, $values->and[0]);
    }

    /**
     * @param Builder $builder
     * @param string $column
     * @param CategorizedValues $values
     * @param string $operator
     * @throws JsonQueryBuilderException
     */
    protected function betweenCallback(Builder $builder, string $column, CategorizedValues $values, string $operator)
    {
        $this->checkAllowedValues($values, $operator);

        if (count($values->and) != 2) {
            throw new JsonQueryBuilderException("Using $operator operator assumes exactly 2 parameters. Wrong number of parameters provided.");
        }

        if (!count($values->and)) {
            throw new JsonQueryBuilderException("No valid arguments for '$operator' operator.");
        }

        $callback = $operator == '<>' ? 'whereBetween' : 'whereNotBetween';

        $builder->{$callback}($column, [$values->and[0], $values->and[1]]);
    }

    /**
     * Should throw exception if anything except '$values->and' is filled out.
     *
     * @param CategorizedValues $values
     * @param string $operator
     * @throws JsonQueryBuilderException
     */
    protected function checkAllowedValues(CategorizedValues $values, string $operator): void
    {
        if ($values->null || $values->notNull || $values->not || $values->notLike || $values->andLike) {
            throw new JsonQueryBuilderException("Wrong parameter type(s) for '$operator' operator.");
        }
    }
}
