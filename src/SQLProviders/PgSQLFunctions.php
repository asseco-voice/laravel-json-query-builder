<?php

namespace Asseco\JsonQueryBuilder\SQLProviders;

class PgSQLFunctions extends SQLFunctions
{
    public static function year($raw)
    {
        return "EXTRACT(YEAR FROM $raw)";
    }

    public static function month($raw)
    {
        return "EXTRACT(MONTH FROM $raw)";
    }

    public static function day($raw)
    {
        return "EXTRACT(DAY FROM $raw)";
    }
}
