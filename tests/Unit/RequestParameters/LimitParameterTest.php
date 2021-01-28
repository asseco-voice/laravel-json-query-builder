<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Tests\Unit\RequestParameters;

use Asseco\JsonQueryBuilder\Config\ModelConfig;
use Asseco\JsonQueryBuilder\RequestParameters\LimitParameter;
use Asseco\JsonQueryBuilder\Tests\TestCase;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Mockery;

class LimitParameterTest extends TestCase
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
        $limitParameter = new LimitParameter([], $this->builder, $this->modelConfig);

        $this->assertEquals('limit', $limitParameter::getParameterName());
    }

    /** @test */
    public function accepts_valid_arguments()
    {
        $limitParameter = new LimitParameter([15], $this->builder, $this->modelConfig);
        $limitParameter->run();

        $this->assertTrue(true);
    }

    /** @test */
    public function rejects_non_numeric_argument()
    {
        $this->expectException(Exception::class);

        $limitParameter = new LimitParameter(['invalid'], $this->builder, $this->modelConfig);
        $limitParameter->run();
    }

    /** @test */
    public function rejects_multiple_arguments()
    {
        $this->expectException(Exception::class);

        $limitParameter = new LimitParameter([1, 1], $this->builder, $this->modelConfig);
        $limitParameter->run();
    }

    /** @test */
    public function produces_query()
    {
        $limitParameter = new LimitParameter([1], $this->builder, $this->modelConfig);
        $limitParameter->run();

        $query = 'select * limit 1';

        $this->assertEquals($query, $this->builder->toSql());
    }

}
