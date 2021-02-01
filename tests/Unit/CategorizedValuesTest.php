<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Tests\Unit\Parsers;

use Asseco\JsonQueryBuilder\CategorizedValues;
use Asseco\JsonQueryBuilder\SearchParser;
use Asseco\JsonQueryBuilder\Tests\TestCase;
use Mockery;

class CategorizedValuesTest extends TestCase
{
    protected SearchParser $searchParser;

    public function setUp(): void
    {
        parent::setUp();

        $this->searchParser = Mockery::mock(SearchParser::class);

        $this->searchParser->type = 'string';
    }

    /** @test */
    public function has_null_value()
    {
        $this->searchParser->values = ['123', '456', 'null', '789'];

        $categorizedValues = new CategorizedValues($this->searchParser);

        $this->assertTrue($categorizedValues->null);
    }

    /** @test */
    public function has_no_null_value()
    {
        $this->searchParser->values = ['123', '456', '789'];

        $categorizedValues = new CategorizedValues($this->searchParser);

        $this->assertFalse($categorizedValues->null);
    }

    /** @test */
    public function has_not_null_value()
    {
        $this->searchParser->values = ['123', '456', '!null', '789'];

        $categorizedValues = new CategorizedValues($this->searchParser);

        $this->assertTrue($categorizedValues->notNull);
    }

    /** @test */
    public function has_no_not_null_value()
    {
        $this->searchParser->values = ['123', '456', '789'];

        $categorizedValues = new CategorizedValues($this->searchParser);

        $this->assertFalse($categorizedValues->notNull);
    }

    /** @test */
    public function has_not_values()
    {
        $this->searchParser->values = ['123', '!456', '!789'];

        $categorizedValues = new CategorizedValues($this->searchParser);

        $this->assertEquals(['456', '789'], $categorizedValues->not);
    }

    /** @test */
    public function has_not_like_values()
    {
        $this->searchParser->values = ['123', '!%456', '!789%'];

        $categorizedValues = new CategorizedValues($this->searchParser);

        $this->assertEquals(['%456', '789%'], $categorizedValues->notLike);
    }

    /** @test */
    public function has_and_values()
    {
        $this->searchParser->values = ['123', '456', '!789'];

        $categorizedValues = new CategorizedValues($this->searchParser);

        $this->assertEquals(['123', '456'], $categorizedValues->and);
    }

    /** @test */
    public function has_and_like_values()
    {
        $this->searchParser->values = ['123', '%456', '789%'];

        $categorizedValues = new CategorizedValues($this->searchParser);

        $this->assertEquals(['%456', '789%'], $categorizedValues->andLike);
    }
}
