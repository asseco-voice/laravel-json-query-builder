<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Types;

abstract class AbstractType
{
    /**
     * Name of the type as it is used within Laravel migrations.
     *
     * @return string
     */
    abstract public static function name(): string;

    /**
     * Prepare/transform values for query if needed.
     *
     * @param  array  $values
     * @return array
     */
    public function prepare(array $values): array
    {
        return $values;
    }
}
