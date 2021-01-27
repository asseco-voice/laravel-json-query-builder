<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Tests\Unit\Config;

use Asseco\JsonQueryBuilder\Config\RequestParametersConfig;
use Asseco\JsonQueryBuilder\Tests\TestCase;
use Exception;

class RequestParametersConfigTest extends TestCase
{
    /** @test */
    public function passes_on_valid_config()
    {
        $requestParameters = new RequestParametersConfig();

        $this->assertNotEmpty($requestParameters->registered);
    }

    /** @test */
    public function throws_on_missing_config()
    {
        $this->expectException(Exception::class);

        config(['asseco-json-query-builder' => []]);

        new RequestParametersConfig();
    }
}
