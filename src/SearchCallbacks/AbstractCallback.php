<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\SearchCallbacks;

use Asseco\JsonQueryBuilder\CategorizedValues;
use Asseco\JsonQueryBuilder\CustomFieldSearchParser;
use Asseco\JsonQueryBuilder\Exceptions\JsonQueryBuilderException;
use Asseco\JsonQueryBuilder\SearchParserInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PDO;

abstract class AbstractCallback
{
    protected Builder $builder;
    protected SearchParserInterface $searchParser;
    protected CategorizedValues $categorizedValues;

    protected const DATE_FIELDS = [
        'date',
    ];

    protected const DATETIME_FIELDS = [
        'datetime',
    ];

    /**
     * AbstractCallback constructor.
     *
     * @param  Builder  $builder
     * @param  SearchParserInterface  $searchParser
     *
     * @throws JsonQueryBuilderException
     */
    public function __construct(Builder $builder, SearchParserInterface $searchParser)
    {
        $this->builder = $builder;
        $this->searchParser = $searchParser;
        $this->categorizedValues = new CategorizedValues($this->searchParser);

        $this->builder->when(
            str_contains($this->searchParser->column, '.'),
            function (Builder $builder) {
                // Hack for whereDoesntHave relation, doesn't work recursively.
                if (str_contains($this->searchParser->column, '!') !== false) {
                    $this->searchParser->column = str_replace('!', '', $this->searchParser->column);
                    $this->appendRelations($builder, $this->searchParser->column, $this->categorizedValues, 'orWhereDoesntHave');

                    return;
                }
                $this->appendRelations($builder, $this->searchParser->column, $this->categorizedValues);
            },
            function (Builder $builder) {
                $this->execute($builder, $this->searchParser->column, $this->categorizedValues);
                $this->checkExecuteForCustomfieldsParameter($builder);
            }
        );
    }

    /**
     * Shorthand operator sign.
     *
     * I.e. '=', '<', '>'...
     *
     * @return string
     */
    abstract public static function operator(): string;

    /**
     * Execute a callback on a given column, providing the array of values.
     *
     * @param  Builder  $builder
     * @param  string  $column
     * @param  CategorizedValues  $values
     *
     * @throws JsonQueryBuilderException
     */
    abstract public function execute(Builder $builder, string $column, CategorizedValues $values): void;

    protected function appendRelations(Builder $builder, string $column, CategorizedValues $values, string $method = 'orWhereHas'): void
    {
        [$relationName, $relatedColumns] = explode('.', $column, 2);

        $builder->{$method}(Str::camel($relationName), function (Builder $builder) use ($relatedColumns, $values) {
            // Support for inner relation calls like model.relation.relation2.relation2_attribute
            if (str_contains($relatedColumns, '.')) {
                $this->appendRelations($builder, $relatedColumns, $values, 'whereHas');

                return;
            }

            // $this->execute($builder, $relatedColumns, $values);
            // need to group those wheres statements....otherwise, there will be OR statement added, and relation would be "broken"
            $builder->where(function ($builder) use ($relatedColumns, $values) {
                $this->execute($builder, $relatedColumns, $values);
            });
            $this->checkExecuteForCustomfieldsParameter($builder);
        });
    }

    /**
     * @param  Builder  $builder
     * @param  string  $column
     * @param  CategorizedValues  $values
     * @param  string  $operator
     *
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

        $method = $this->isDate($this->searchParser->type) ? 'whereDate' : 'where';
        $builder->{$method}($column, $operator, $values->and[0]);
    }

    /**
     * @param  Builder  $builder
     * @param  string  $column
     * @param  CategorizedValues  $values
     * @param  string  $operator
     *
     * @throws JsonQueryBuilderException
     */
    protected function betweenCallback(Builder $builder, string $column, CategorizedValues $values, string $operator)
    {
        $this->checkAllowedValues($values, $operator);

        if (count($values->and) != 2) {
            throw new JsonQueryBuilderException("Using $operator operator assumes exactly 2 parameters. Wrong number of parameters provided.");
        }

        $callback = $operator == '<>' ? 'whereBetween' : 'whereNotBetween';

        $builder->{$callback}($column, [$values->and[0], $values->and[1]]);
    }

    /**
     * @param  Builder  $builder
     * @param  string  $column
     * @param  CategorizedValues  $values
     * @param  string  $operator
     *
     * @throws JsonQueryBuilderException
     */
    protected function containsCallback(Builder $builder, string $column, CategorizedValues $values, string $operator)
    {
        if ($values->andLike) {
            $builder->where($column, $this->getLikeOperator(), '%' . $values->andLike[0] . '%');
        }
        if ($values->and) {
            foreach ($values->and as $andValue) {
                $builder->orWhere($column, $this->getLikeOperator(), '%' . $andValue . '%');
            }
        }
    }

    /**
     * @param  Builder  $builder
     * @param  string  $column
     * @param  CategorizedValues  $values
     * @param  string  $operator
     *
     * @throws JsonQueryBuilderException
     */
    protected function endsWithCallback(Builder $builder, string $column, CategorizedValues $values, string $operator)
    {
        if ($values->andLike) {
            $builder->where($column, $this->getLikeOperator(), '%' . $values->andLike[0]);
        }
        if ($values->and) {
            foreach ($values->and as $andValue) {
                $builder->orWhere($column, $this->getLikeOperator(), '%' . $andValue);
            }
        }
    }

    /**
     * @param  Builder  $builder
     * @param  string  $column
     * @param  CategorizedValues  $values
     * @param  string  $operator
     *
     * @throws JsonQueryBuilderException
     */
    protected function startsWithCallback(Builder $builder, string $column, CategorizedValues $values, string $operator)
    {
        if ($values->andLike) {
            $builder->where($column, $this->getLikeOperator(), $values->andLike[0] . '%');
        }
        if ($values->and) {
            foreach ($values->and as $andValue) {
                $builder->orWhere($column, $this->getLikeOperator(), $andValue . '%');
            }
        }
    }

    /**
     * Should throw exception if anything except '$values->and' is filled out.
     *
     * @param  CategorizedValues  $values
     * @param  string  $operator
     *
     * @throws JsonQueryBuilderException
     */
    protected function checkAllowedValues(CategorizedValues $values, string $operator): void
    {
        if ($values->null || $values->notNull || $values->not || $values->notLike || $values->andLike) {
            throw new JsonQueryBuilderException("Wrong parameter type(s) for '$operator' operator.");
        }
    }

    protected function isDate(string $type): bool
    {
        return in_array($type, self::DATE_FIELDS);
    }

    protected function isDateTime(string $type): bool
    {
        return in_array($type, self::DATETIME_FIELDS);
    }

    //Hack to enable case-insensitive search when using PostgreSQL database
    protected function getLikeOperator(): string
    {
        if (DB::connection()->getPDO()->getAttribute(PDO::ATTR_DRIVER_NAME) == 'pgsql') {
            return 'ILIKE';
        }

        return 'LIKE';
    }

    protected function checkExecuteForCustomfieldsParameter($builder)
    {
        if ($this->searchParser instanceof CustomFieldSearchParser) {
            $builder->where($this->searchParser->cf_field_identificator, '=', $this->searchParser->cf_field_value);
        }
    }
}
