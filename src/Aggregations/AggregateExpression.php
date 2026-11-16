<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Aggregations;

use Asseco\JsonQueryBuilder\Exceptions\JsonQueryBuilderException;

/**
 * Turns a 'returns' argument such as "count:distinct:name" or "min:year:created_at" into SQL.
 *
 * Shape: [aggregate[:distinct]][:date part]:column
 *   - aggregate: avg, count, max, min, sum (outermost)
 *   - distinct: only directly after count
 *   - date part: year, month, day
 *   - column: one of the given model columns, or '*' for a plain "count:*"
 *
 * Nothing from the request reaches the SQL as text: function names come from the lists below and
 * the column must equal one of the allowed columns exactly, then it is quoted by the grammar.
 */
class AggregateExpression
{
    public const SEPARATOR = ':';

    public const AGGREGATES = ['avg', 'count', 'max', 'min', 'sum'];
    public const DATE_PARTS = ['year', 'month', 'day'];
    public const DISTINCT = 'distinct';

    public static function isAggregate(string $argument): bool
    {
        return str_contains($argument, self::SEPARATOR);
    }

    /**
     * @param  string  $argument  e.g. "count:distinct:name"
     * @param  array  $allowedColumns  columns the caller may use (model columns minus forbidden ones)
     * @param  string  $driver  connection driver name (pgsql, mysql, mariadb, sqlite, sqlsrv)
     * @param  callable  $wrap  quotes an identifier, i.e. the query grammar's wrap()
     * @return string "<expression> as <alias>"
     *
     * @throws JsonQueryBuilderException
     */
    public static function toSql(string $argument, array $allowedColumns, string $driver, callable $wrap): string
    {
        $tokens = explode(self::SEPARATOR, $argument);
        $column = array_pop($tokens);

        [$aggregate, $distinct, $datePart] = self::parseFunctions($tokens, $argument);

        if ($column === '*') {
            if ($aggregate !== 'count' || $distinct || $datePart) {
                throw new JsonQueryBuilderException("'*' can only be used as 'count:*' in '$argument'.");
            }
        } elseif (!in_array($column, $allowedColumns, true)) {
            throw new JsonQueryBuilderException("Invalid column '$column' in '$argument'.");
        }

        $sql = $column === '*' ? '*' : $wrap($column);

        if ($datePart) {
            $sql = self::datePart($datePart, $sql, $driver);
        }

        if ($aggregate) {
            $sql = strtoupper($aggregate) . '(' . ($distinct ? 'DISTINCT ' : '') . $sql . ')';
        }

        $alias = implode('_', array_filter([$aggregate, $distinct ? self::DISTINCT : null, $datePart,
            $column === '*' ? null : $column, ]));

        return $sql . ' as ' . $wrap($alias);
    }

    /**
     * @return array{0: ?string, 1: bool, 2: ?string} aggregate, distinct, date part
     *
     * @throws JsonQueryBuilderException
     */
    protected static function parseFunctions(array $tokens, string $argument): array
    {
        $aggregate = null;
        $distinct = false;
        $datePart = null;

        if ($tokens && in_array($tokens[0], self::AGGREGATES, true)) {
            $aggregate = array_shift($tokens);

            if ($aggregate === 'count' && $tokens && $tokens[0] === self::DISTINCT) {
                $distinct = (bool) array_shift($tokens);
            }
        }

        if ($tokens && in_array($tokens[0], self::DATE_PARTS, true)) {
            $datePart = array_shift($tokens);
        }

        if ($tokens || (!$aggregate && !$datePart)) {
            throw new JsonQueryBuilderException(
                "Invalid aggregation '$argument'. Expected [aggregate[:distinct]][:date part]:column with aggregate one of "
                . implode(', ', self::AGGREGATES) . ', date part one of ' . implode(', ', self::DATE_PARTS)
                . " and 'distinct' only after count."
            );
        }

        return [$aggregate, $distinct, $datePart];
    }

    /**
     * @throws JsonQueryBuilderException
     */
    protected static function datePart(string $part, string $column, string $driver): string
    {
        return match ($driver) {
            'pgsql'            => 'EXTRACT(' . strtoupper($part) . " FROM $column)",
            'mysql', 'mariadb' => strtoupper($part) . "($column)",
            'sqlite'           => "CAST(strftime('" . ['year' => '%Y', 'month' => '%m', 'day' => '%d'][$part] . "', $column) AS INTEGER)",
            'sqlsrv'           => "DATEPART($part, $column)",
            default            => throw new JsonQueryBuilderException("Date functions are not supported for the '$driver' driver."),
        };
    }
}
