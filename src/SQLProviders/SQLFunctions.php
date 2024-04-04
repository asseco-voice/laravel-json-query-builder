<?php

namespace Asseco\JsonQueryBuilder\SQLProviders;

use Asseco\JsonQueryBuilder\Exceptions\JsonQueryBuilderException;

class SQLFunctions
{
    public const DB_FUNCTIONS = [
        "avg",
        "count",
        "max",
        "min",
        "sum",
        "distinct",

        "year",
        "month",
        "day",
    ];

    final public static function validateArgument(string $argument): void
    {
        $split  = explode(":", $argument);
        $column = array_pop($split);

        if (!preg_match("/^[a-zA-Z_][a-zA-Z0-9_]*|\*$/", $column) || in_array($column, self::DB_FUNCTIONS)) {
            throw new JsonQueryBuilderException(
                "Invalid column name: {$column}."
            );
        }

        if ($invalidFns = array_diff($split, self::DB_FUNCTIONS)) {
            throw new JsonQueryBuilderException(
                "Invalid function: " . join(",", $invalidFns) . "."
            );
        }
    }

    final public static function __callStatic($fn, $args)
    {
        if (!in_array($fn, self::DB_FUNCTIONS)) {
            throw new JsonQueryBuilderException(
                "Invalid function: $fn."
            );
        }

        if (method_exists(self::class, $fn)) {
            return self::$fn($args);
        }

        return $fn . "($args[0])";
    }
}
