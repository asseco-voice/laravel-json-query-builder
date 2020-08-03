<?php

namespace Voice\JsonQueryBuilder\Exceptions;

use Exception;
use Throwable;

class JsonQueryBuilderException extends Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        $message = "[JsonQueryBuilder] $message";
        parent::__construct($message, $code, $previous);
    }
}
