<?php

declare (strict_types = 1);

namespace Asseco\JsonQueryBuilder\Tests\Unit\RequestParameters;

use Asseco\JsonQueryBuilder\Config\ModelConfig;
use Asseco\JsonQueryBuilder\RequestParameters\ReturnsParameter;
use Asseco\JsonQueryBuilder\Tests\TestCase;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Mockery;

class ReturnsParameterTest extends TestCase
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
        $returnsParameter = new ReturnsParameter([], $this->builder, $this->modelConfig);

        $this->assertEquals('returns', $returnsParameter::getParameterName());
    }

    /** @test */
    public function accepts_valid_arguments()
    {
        $returnsParameter = new ReturnsParameter(
            ['attribute1', 'attribute2'], $this->builder, $this->modelConfig);
        $returnsParameter->run();

        $this->assertTrue(true);
    }

    /** @test */
    public function rejects_empty_argument()
    {
        $this->expectException(Exception::class);

        $returnsParameter = new ReturnsParameter([], $this->builder, $this->modelConfig);
        $returnsParameter->run();
    }

    /** @test */
    public function produces_query()
    {
        $returnsParameter = new ReturnsParameter(
            ['attribute1', 'attribute2'], $this->builder, $this->modelConfig);
        $returnsParameter->run();

        $query = 'select "attribute1", "attribute2"';

        $this->assertEquals($query, $this->builder->toSql());
    }

    /** @test */
    public function produces_aggregation_query()
    {
        $returnsParameter = new ReturnsParameter(
            ['count:attribute1', 'count:attribute2'], $this->builder, $this->modelConfig);
        $returnsParameter->run();

        $query = 'select count("attribute1") as count_attribute1, count("attribute2") as count_attribute2';

        $this->assertEquals($query, $this->builder->toSql());
    }
}
