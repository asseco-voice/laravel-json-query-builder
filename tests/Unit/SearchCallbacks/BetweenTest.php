<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Tests\Unit\SearchCallbacks;

use Asseco\JsonQueryBuilder\SearchCallbacks\Between;
use Asseco\JsonQueryBuilder\SearchParser;
use Asseco\JsonQueryBuilder\Tests\TestCase;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Mockery;

class BetweenTest extends TestCase
{
    protected Builder $builder;
    protected SearchParser $searchParser;

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
    }

    /** @test */
    public function produces_query()
    {
        $this->searchParser->values = ['123', '456'];

        new Between($this->builder, $this->searchParser);

        $sql = 'select * where "test" between ? and ?';

        $this->assertEquals($sql, $this->builder->toSql());
    }

    /** @test */
    public function fails_on_invalid_parameters()
    {
        $this->expectException(Exception::class);

        $this->searchParser->values = ['invalid'];

        new Between($this->builder, $this->searchParser);
    }
}
