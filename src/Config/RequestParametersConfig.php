<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Config;

class RequestParametersConfig extends SearchConfig
{
    protected function configKey(): string
    {
        return 'request_parameters';
    }
}
