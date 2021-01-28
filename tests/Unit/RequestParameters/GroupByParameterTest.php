<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Tests\Unit\RequestParameters;

use Asseco\JsonQueryBuilder\Config\ModelConfig;
use Asseco\JsonQueryBuilder\RequestParameters\GroupByParameter;
use Asseco\JsonQueryBuilder\Tests\TestCase;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Mockery;

class GroupByParameterTest extends TestCase
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
        $groupByParameter = new GroupByParameter([], $this->builder, $this->modelConfig);

        $this->assertEquals('group_by', $groupByParameter::getParameterName());
    }

    /** @test */
    public function accepts_valid_arguments()
    {
        $groupByParameter = new GroupByParameter(
            ['attribute1', 'attribute2'], $this->builder, $this->modelConfig);
        $groupByParameter->run();

        $this->assertTrue(true);
    }

    /** @test */
    public function rejects_empty_argument()
    {
        $this->expectException(Exception::class);

        $groupByParameter = new GroupByParameter([], $this->builder, $this->modelConfig);
        $groupByParameter->run();
    }

    /** @test */
    public function produces_query()
    {
        $groupByParameter = new GroupByParameter([true], $this->builder, $this->modelConfig);
        $groupByParameter->run();

        $query = 'select * group by "1"';

        $this->assertEquals($query, $this->builder->toSql());
    }

}
