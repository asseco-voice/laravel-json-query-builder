<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Types;

use Asseco\JsonQueryBuilder\Exceptions\JsonQueryBuilderException;

class BooleanType extends AbstractType
{
    public static function name(): string
    {
        return 'boolean';
    }

    /**
     * @param  array  $values
     * @return array
     *
     * @throws JsonQueryBuilderException
     */
    public function prepare(array $values): array
    {
        foreach ($values as &$value) {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($value === null) {
                throw new JsonQueryBuilderException('Wrong argument type provided');
            }
        }

        return $values;
    }
}
