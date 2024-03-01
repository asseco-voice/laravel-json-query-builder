<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Tests\Unit\Config;

use Asseco\JsonQueryBuilder\Config\OperatorsConfig;
use Asseco\JsonQueryBuilder\SearchCallbacks\Equals;
use Asseco\JsonQueryBuilder\Tests\TestCase;
use Exception;

class OperatorsConfigTest extends TestCase
{
    /** @test */
    public function passes_on_valid_config()
    {
        $operators = new OperatorsConfig();

        $this->assertNotEmpty($operators->registered);
    }

    /** @test */
    public function throws_on_missing_config()
    {
        $this->expectException(Exception::class);

        config(['asseco-json-query-builder' => []]);

        new OperatorsConfig();
    }

    /** @test */
    public function returns_registered_operators()
    {
        $operatorsConfig = new OperatorsConfig();

        $expected = ['!<>', '<=', '>=', '<>', '!=', '=', '<', '>', 'contains', 'starts_with', 'ends_with'];

        $this->assertEquals($expected, $operatorsConfig->getOperators());
    }

    /** @test */
    public function returns_class_from_given_operator()
    {
        $operatorsConfig = new OperatorsConfig();

        $this->assertEquals(Equals::class, $operatorsConfig->getCallbackClassFromOperator('='));
    }

    /** @test */
    public function throws_exception_on_non_existing_operator()
    {
        $this->expectException(Exception::class);

        $operatorsConfig = new OperatorsConfig();

        $operatorsConfig->getCallbackClassFromOperator('123');
    }
}
