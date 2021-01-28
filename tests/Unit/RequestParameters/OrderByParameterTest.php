<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Tests\Unit\RequestParameters;

use Asseco\JsonQueryBuilder\Config\ModelConfig;
use Asseco\JsonQueryBuilder\RequestParameters\OrderByParameter;
use Asseco\JsonQueryBuilder\Tests\TestCase;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Mockery;

class OrderByParameterTest extends TestCase
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
        $orderByParameter = new OrderByParameter([], $this->builder, $this->modelConfig);

        $this->assertEquals('order_by', $orderByParameter::getParameterName());
    }

    /** @test */
    public function accepts_valid_arguments()
    {
        $orderByParameter = new OrderByParameter(
            ['attribute1', 'attribute2' => 'desc'], $this->builder, $this->modelConfig);
        $orderByParameter->run();

        $this->assertTrue(true);
    }

    /** @test */
    public function rejects_empty_argument()
    {
        $this->expectException(Exception::class);

        $orderByParameter = new OrderByParameter([], $this->builder, $this->modelConfig);
        $orderByParameter->run();
    }

    /** @test */
    public function produces_query()
    {
        $orderByParameter = new OrderByParameter(
            ['attribute1', 'attribute2' => 'desc'], $this->builder, $this->modelConfig);
        $orderByParameter->run();

        $query = 'select * order by "attribute1" asc, "attribute2" desc';

        $this->assertEquals($query, $this->builder->toSql());
    }

    /** @test */
    public function produces_query_2()
    {
        $orderByParameter = new OrderByParameter(
            ['attribute1' => 'desc', 'attribute2' => 'asc'], $this->builder, $this->modelConfig);
        $orderByParameter->run();

        $query = 'select * order by "attribute1" desc, "attribute2" asc';

        $this->assertEquals($query, $this->builder->toSql());
    }
}
