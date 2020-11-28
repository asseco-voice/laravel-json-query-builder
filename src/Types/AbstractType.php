<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Types;

abstract class AbstractType
{
    /**
     * Child class MUST extend a NAME constant.
     * This is a Laravel friendly name for columns based on Laravel migration column types.
     *
     * @return string
     */
    public static function getTypeName(): string
    {
        return static::NAME;
    }

    /**
     * Prepare/transform values for query if needed.
     *
     * @param array $values
     * @return array
     */
    public function prepare(array $values): array
    {
        return $values;
    }
}
