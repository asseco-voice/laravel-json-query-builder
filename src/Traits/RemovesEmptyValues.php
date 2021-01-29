<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Traits;

trait RemovesEmptyValues
{
    /**
     * Remove empty values from a given array.
     *
     * I.e. given the input "=1;2;;;3;null;  ;  4  ;          ;5"
     * the following will be returned:
     * [ '1', '2', '3', 'null', '4', '5' ]
     *
     * null is absolutely valid input for query
     *
     * @param array $input
     * @return array
     */
    protected function removeEmptyValues(array $input): array
    {
        $trimmedInput = array_map('trim', $input);

        $deleteKeys = array_keys(
            array_filter($trimmedInput, fn ($item) => $item == '')
        );

        foreach ($deleteKeys as $deleteKey) {
            unset($trimmedInput[$deleteKey]);
        }

        return $trimmedInput;
    }
}
