<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Tests\Unit\RequestParameters;

use Asseco\JsonQueryBuilder\Config\ModelConfig;
use Asseco\JsonQueryBuilder\RequestParameters\RelationsParameter;
use Asseco\JsonQueryBuilder\Tests\TestCase;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Mockery;

class RelationsParameterTest extends TestCase
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
        $relationsParameter = new RelationsParameter([], $this->builder, $this->modelConfig);

        $this->assertEquals('relations', $relationsParameter::getParameterName());
    }

    /** @test */
    public function accepts_valid_arguments()
    {
        $relationsParameter = new RelationsParameter(
            ['attribute1', 'attribute2'], $this->builder, $this->modelConfig);
        $relationsParameter->run();

        $this->assertTrue(true);
    }

    /** @test */
    public function rejects_empty_argument()
    {
        $this->expectException(Exception::class);

        $relationsParameter = new RelationsParameter([], $this->builder, $this->modelConfig);
        $relationsParameter->run();
    }

    /** @test */
    public function relations_do_not_produce_query_like_this_so_this_test_is_useless()
    {
        $relationsParameter = new RelationsParameter(
            ['attribute1', 'attribute2'], $this->builder, $this->modelConfig);
        $relationsParameter->run();

        $query = 'select *';

        $this->assertEquals($query, $this->builder->toSql());
    }
}
