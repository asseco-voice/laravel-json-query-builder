<?php

namespace Voice\JsonQueryBuilder\Types;

use Voice\JsonQueryBuilder\Exceptions\JsonQueryBuilderException;

class BooleanType extends AbstractType
{
    const NAME = 'boolean';

    /**
     * Prepare/transform values for query if needed.
     *
     * @param array $values
     *
     * @throws JsonQueryBuilderException
     *
     * @return array
     */
    public function prepare(array $values): array
    {
        foreach ($values as &$value) {
            $value = strtolower($value);

            if (in_array($value, [1, '1', 'true'], true)) {
                $value = 1;
            } elseif (in_array($value, [0, '0', 'false'], true)) {
                $value = 0;
            }

            if (!is_numeric($value) || !in_array($value, [0, 1])) {
                throw new JsonQueryBuilderException('wrong argument type provided');
            }
        }

        return $values;
    }
}
