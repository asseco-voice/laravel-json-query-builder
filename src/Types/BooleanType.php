<?php

namespace Voice\JsonQueryBuilder\Types;

use Voice\JsonQueryBuilder\Exceptions\SearchException;

class BooleanType extends AbstractType
{
    const NAME = 'boolean';

    /**
     * Prepare/transform values for query if needed
     *
     * @param array $values
     * @return array
     * @throws SearchException
     */
    function prepare(array $values): array
    {
        foreach ($values as &$value) {
            $value = strtolower($value);

            if (in_array($value, [1, '1', 'true'], true)) {
                $value = 1;
            } else if (in_array($value, [0, '0', 'false'], true)) {
                $value = 0;
            }

            if (!is_numeric($value) || !in_array($value, [0, 1])) {
                throw new SearchException("[Search] wrong argument type provided");
            }
        }

        return $values;
    }
}
