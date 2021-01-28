<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Tests\Unit\SearchCallbacks;

use Asseco\JsonQueryBuilder\Config\OperatorsConfig;
use Asseco\JsonQueryBuilder\SearchCallbacks\Equals;
use Asseco\JsonQueryBuilder\SearchParser;
use Asseco\JsonQueryBuilder\Tests\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Mockery;

class EqualsTest extends TestCase
{
    protected Builder $builder;
    protected SearchParser $searchParser;
    protected OperatorsConfig $operatorsConfig;

    public function setUp(): void
    {
        parent::setUp();

        /**
         * @var Builder $builder
         */
        $this->builder = app(Builder::class);

        $this->searchParser = Mockery::mock(SearchParser::class);
        $this->searchParser->type = 'test';
        $this->searchParser->column = 'test';

        $this->operatorsConfig = Mockery::mock(OperatorsConfig::class);
    }

    /** @test */
    public function produces_query()
    {
        $this->searchParser->values = ['123'];

        new Equals($this->builder, $this->searchParser, $this->operatorsConfig);

        $sql = 'select * where "test" in (?)';

        $this->assertEquals($sql, $this->builder->toSql());
    }
}
