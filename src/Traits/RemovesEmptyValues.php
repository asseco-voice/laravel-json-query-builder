<?php

declare(strict_types=1);

namespace Voice\JsonQueryBuilder\Traits;

trait RemovesEmptyValues
{
    /**
     * Remove empty values from a given array.
     *
     * @param array $input
     * @return array
     */
    protected function removeEmptyValues(array $input): array
    {
        $trimmedInput = array_map('trim', $input);

        $deleteKeys = array_keys(
            array_filter($trimmedInput, fn($item) => $item == '')
        );

        foreach ($deleteKeys as $deleteKey) {
            unset($trimmedInput[$deleteKey]);
        }

        return $trimmedInput;
    }
}
