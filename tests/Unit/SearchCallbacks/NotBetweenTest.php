<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Tests\Unit\SearchCallbacks;

use Asseco\JsonQueryBuilder\Config\OperatorsConfig;
use Asseco\JsonQueryBuilder\SearchCallbacks\NotBetween;
use Asseco\JsonQueryBuilder\SearchParser;
use Asseco\JsonQueryBuilder\Tests\TestCase;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Mockery;

class NotBetweenTest extends TestCase
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
        $this->searchParser->values = ['123', '456'];

        new NotBetween($this->builder, $this->searchParser, $this->operatorsConfig);

        $sql = 'select * where "test" not between ? and ?';

        $this->assertEquals($sql, $this->builder->toSql());
    }

    /** @test */
    public function fails_on_invalid_parameters()
    {
        $this->expectException(Exception::class);

        $this->searchParser->values = ['invalid'];

        new NotBetween($this->builder, $this->searchParser, $this->operatorsConfig);
    }
}
