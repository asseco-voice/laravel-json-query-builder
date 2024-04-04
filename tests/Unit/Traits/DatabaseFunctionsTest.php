<?php

namespace Asseco\JsonQueryBuilder\Tests\Unit\Traits;

use Asseco\JsonQueryBuilder\Config\ModelConfig;
use Asseco\JsonQueryBuilder\RequestParameters\AbstractParameter;
use Asseco\JsonQueryBuilder\SQLProviders\SQLFunctions;
use Asseco\JsonQueryBuilder\Tests\TestCase;
use Asseco\JsonQueryBuilder\Traits\DatabaseFunctions;
use Illuminate\Database\Eloquent\Builder;

class TestParameterClass extends AbstractParameter
{
    use DatabaseFunctions;

    public static function getParameterName(): string
    {
        return 'test';
    }
    protected function appendQuery(): void
    {
        $this->prepareArguments();
        $this->builder->addSelect($this->arguments);
    }
}

class DatabaseFunctionsTest extends TestCase
{
    protected Builder $builder;
    protected ModelConfig $modelConfig;

    public function setUp(): void
    {
        parent::setUp();
        $this->builder     = app(Builder::class);
        $this->modelConfig = \Mockery::mock(ModelConfig::class);

    }

    public function test_it_not_throw_with_regular_parameters()
    {
        $parameter = new TestParameterClass(["column_a", "column_b", "column_c", "column_d"], $this->builder, $this->modelConfig);
        $this->assertNull($parameter->run());
        $this->assertEquals('select "column_a", "column_b", "column_c", "column_d"', $parameter->builder->toSql());
    }

    public function test_it_apply_aggregation_functions()
    {
        foreach (SQLFunctions::DB_FUNCTIONS as $fn) {
            $builder   = $this->builder->clone();
            $parameter = new TestParameterClass(["$fn:column"], $builder, $this->modelConfig);
            $this->assertNull($parameter->run());
            $this->assertEquals("select $fn(column) as {$fn}_column", $builder->toSql());
        }

    }

    public function test_it_apply_nested_aggregation_functions()
    {
        $parameter = new TestParameterClass(["avg:day:column"], $this->builder, $this->modelConfig);
        $this->assertNull($parameter->run());
        $this->assertEquals("select avg(day(column)) as avg_day_column", $this->builder->toSql());
    }

    public function test_it_uses_pgsql_syntax()
    {
        app("config")->set("database.default", "pgsql");
        $parameter = new TestParameterClass(["avg:day:column"], $this->builder, $this->modelConfig);
        $this->assertNull($parameter->run());
        $this->assertEquals("select avg(EXTRACT(DAY FROM column)) as avg_day_column", $this->builder->toSql());
    }
}
