<?php

namespace Asseco\JsonQueryBuilder\RequestParameters;

interface Parameter
{
    /**
     * JSON key by which the parameter will be recognized.
     * @return string
     */
    public static function getParameterName(): string;

    public function run(): void;
}
