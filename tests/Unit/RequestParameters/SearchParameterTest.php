<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Tests\Unit\RequestParameters;

use Asseco\JsonQueryBuilder\Config\ModelConfig;
use Asseco\JsonQueryBuilder\RequestParameters\SearchParameter;
use Asseco\JsonQueryBuilder\Tests\TestCase;
use Asseco\JsonQueryBuilder\Tests\TestModel;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Mockery;

class SearchParameterTest extends TestCase
{
    protected Builder $builder;
    protected ModelConfig $modelConfig;

    public function setUp(): void
    {
        parent::setUp();

        $this->builder = app(Builder::class);
        $this->builder->setModel(new TestModel());

        $this->modelConfig = Mockery::mock(ModelConfig::class);
        $this->modelConfig->shouldReceive('getForbidden')->andReturn([]);
        $this->modelConfig->shouldReceive('getModelColumns')->andReturn([]);
    }

    protected function createSearchParameter(array $arguments): SearchParameter
    {
        return new SearchParameter($arguments, $this->builder, $this->modelConfig);
    }

    /** @test */
    public function has_a_name()
    {
        $searchParameter = $this->createSearchParameter([]);

        $this->assertEquals('search', $searchParameter::getParameterName());
    }

    /** @test */
    public function accepts_valid_arguments()
    {
        $arguments = [
            'attribute1' => '=123',
            'attribute2' => '=456'
        ];

        $searchParameter = $this->createSearchParameter($arguments);
        $searchParameter->run();

        $this->assertTrue(true);
    }

    /** @test */
    public function rejects_empty_argument()
    {
        $this->expectException(Exception::class);

        $searchParameter = $this->createSearchParameter([]);
        $searchParameter->run();
    }

    /** @test */
    public function produces_where_in_query()
    {
        $arguments = [
            'attribute1' => '=123',
            'attribute2' => '=456'
        ];

        $searchParameter = $this->createSearchParameter($arguments);
        $searchParameter->run();

        $query = 'select * from "test" where ((("attribute1" in (?))) and (("attribute2" in (?))))';

        $this->assertEquals($query, $this->builder->toSql());
    }

    /** @test */
    public function produces_where_in_multiple_query()
    {
        $arguments = [
            'attribute1' => '=123;456',
        ];

        $searchParameter = $this->createSearchParameter($arguments);
        $searchParameter->run();

        $query = 'select * from "test" where ((("attribute1" in (?, ?))))';

        $this->assertEquals($query, $this->builder->toSql());
    }

    /** @test */
    public function produces_where_not_in_query()
    {
        $arguments = [
            'attribute1' => '!=123',
        ];

        $searchParameter = $this->createSearchParameter($arguments);
        $searchParameter->run();

        $query = 'select * from "test" where ((("attribute1" not in (?))))';

        $this->assertEquals($query, $this->builder->toSql());
    }

    /** @test */
    public function produces_where_not_in_multiple_query()
    {
        $arguments = [
            'attribute1' => '!=123;456',
        ];

        $searchParameter = $this->createSearchParameter($arguments);
        $searchParameter->run();

        $query = 'select * from "test" where ((("attribute1" not in (?, ?))))';

        $this->assertEquals($query, $this->builder->toSql());
    }

    /** @test */
    public function produces_less_than_query()
    {
        $arguments = [
            'attribute1' => '<123',
        ];

        $searchParameter = $this->createSearchParameter($arguments);
        $searchParameter->run();

        $query = 'select * from "test" where ((("attribute1" < ?)))';

        $this->assertEquals($query, $this->builder->toSql());
    }

    /** @test */
    public function produces_less_than_or_equals_query()
    {
        $arguments = [
            'attribute1' => '<=123',
        ];

        $searchParameter = $this->createSearchParameter($arguments);
        $searchParameter->run();

        $query = 'select * from "test" where ((("attribute1" <= ?)))';

        $this->assertEquals($query, $this->builder->toSql());
    }

    /** @test */
    public function produces_greater_than_query()
    {
        $arguments = [
            'attribute1' => '>123',
        ];

        $searchParameter = $this->createSearchParameter($arguments);
        $searchParameter->run();

        $query = 'select * from "test" where ((("attribute1" > ?)))';

        $this->assertEquals($query, $this->builder->toSql());
    }

    /** @test */
    public function produces_greater_than_or_equals_query()
    {
        $arguments = [
            'attribute1' => '>=123',
        ];

        $searchParameter = $this->createSearchParameter($arguments);
        $searchParameter->run();

        $query = 'select * from "test" where ((("attribute1" >= ?)))';

        $this->assertEquals($query, $this->builder->toSql());
    }

    /** @test */
    public function produces_between_query()
    {
        $arguments = [
            'attribute1' => '<>123;456',
        ];

        $searchParameter = $this->createSearchParameter($arguments);
        $searchParameter->run();

        $query = 'select * from "test" where ((("attribute1" between ? and ?)))';

        $this->assertEquals($query, $this->builder->toSql());
    }


    /** @test */
    public function produces_not_between_query()
    {
        $arguments = [
            'attribute1' => '!<>123;456',
        ];

        $searchParameter = $this->createSearchParameter($arguments);
        $searchParameter->run();

        $query = 'select * from "test" where ((("attribute1" not between ? and ?)))';

        $this->assertEquals($query, $this->builder->toSql());
    }
}
