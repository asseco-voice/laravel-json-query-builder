<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Tests\Unit\RequestParameters;

use Asseco\JsonQueryBuilder\Config\ModelConfig;
use Asseco\JsonQueryBuilder\RequestParameters\SoftDeletedParameter;
use Asseco\JsonQueryBuilder\Tests\TestCase;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Mockery;

class SoftDeletedParameterTest extends TestCase
{
    protected Builder $builder;
    protected ModelConfig $modelConfig;

    public function setUp(): void
    {
        parent::setUp();

        $this->builder = app(Builder::class);

        $this->modelConfig = Mockery::mock(ModelConfig::class);
    }

    /** @test */
    public function has_a_name()
    {
        $countParameter = new SoftDeletedParameter([], $this->builder, $this->modelConfig);

        $this->assertEquals('soft_deleted', $countParameter::getParameterName());
    }

    /** @test */
    public function accepts_valid_arguments()
    {
        foreach ([1, '1', true, 'true'] as $validArgument) {
            $countParameter = new SoftDeletedParameter([$validArgument], $this->builder, $this->modelConfig);
            $countParameter->run();
        }

        $this->assertTrue(true);
    }

    /** @test */
    public function rejects_non_bool_argument()
    {
        $this->expectException(Exception::class);

        $countParameter = new SoftDeletedParameter(['invalid'], $this->builder, $this->modelConfig);
        $countParameter->run();
    }

    /** @test */
    public function rejects_multiple_arguments()
    {
        $this->expectException(Exception::class);

        $countParameter = new SoftDeletedParameter([1, 1], $this->builder, $this->modelConfig);
        $countParameter->run();
    }
}