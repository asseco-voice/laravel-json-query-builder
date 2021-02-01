<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Tests\Unit\SearchCallbacks;

use Asseco\JsonQueryBuilder\Tests\TestCase;
use Asseco\JsonQueryBuilder\Types\BooleanType;
use Exception;

class BooleanTypeTest extends TestCase
{
    /** @test */
    public function standardizes_output_to_boolean()
    {
        $type = new BooleanType();

        $expected = [1, 1, 1, 1, 1, 0, 0, 0, 0, 0];
        $actual = $type->prepare([1, '1', 'true', 'yes', 'on', 0, '0', 'false', 'no', 'off']);

        $this->assertEquals($expected, $actual);
    }

    /** @test */
    public function throws_on_invalid_input()
    {
        $this->expectException(Exception::class);

        $type = new BooleanType();

        $type->prepare(['non_boolean_value']);
    }
}
