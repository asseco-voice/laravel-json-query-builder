<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Tests\Unit\Parsers;

use Asseco\JsonQueryBuilder\Config\ModelConfig;
use Asseco\JsonQueryBuilder\JsonQuery;
use Asseco\JsonQueryBuilder\Tests\TestCase;
use Asseco\JsonQueryBuilder\Tests\TestModel;
use Exception;
use Illuminate\Database\Eloquent\Builder;

class JsonQueryTest extends TestCase
{
    protected Builder $builder;
    protected ModelConfig $modelConfig;

    public function setUp(): void
    {
        parent::setUp();

        $this->builder = app(Builder::class);
        $this->builder->setModel(new TestModel());
    }

    /** @test */
    public function throws_on_existing_models()
    {
        $this->expectException(Exception::class);

        $this->builder->getModel()->exists = true;

        new JsonQuery($this->builder, []);
    }

    /** @test */
    public function searches_single_attribute()
    {
        $input = [
            'search' => [
                'att1' => '=1',
            ],
        ];

        $jsonQuery = new JsonQuery($this->builder, $input);
        $jsonQuery->search();

        $sql = 'select * from "test" where ((("att1" in (?))))';

        $this->assertEquals($sql, $this->builder->toSql());
    }

    /** @test */
    public function searches_multiple_attributes()
    {
        $input = [
            'search' => [
                'att1' => '=1;2;3',
                'att2' => '=4;5;6',
            ],
        ];

        $jsonQuery = new JsonQuery($this->builder, $input);
        $jsonQuery->search();

        $sql = 'select * from "test" where ((("att1" in (?, ?, ?))) and (("att2" in (?, ?, ?))))';

        $this->assertEquals($sql, $this->builder->toSql());
    }

    /** @test */
    public function searches_negated_attributes()
    {
        $input = [
            'search' => [
                'att1' => '=1;!2;!3',
            ],
        ];

        $jsonQuery = new JsonQuery($this->builder, $input);
        $jsonQuery->search();

        $sql = 'select * from "test" where ((("att1" in (?) and "att1" not in (?, ?))))';

        $this->assertEquals($sql, $this->builder->toSql());
    }

    /** @test */
    public function searches_wildcard_attributes()
    {
        $input = [
            'search' => [
                'att1' => '=1;%2;3%',
            ],
        ];

        $jsonQuery = new JsonQuery($this->builder, $input);
        $jsonQuery->search();

        $sql = 'select * from "test" where ((("att1" LIKE ? and "att1" LIKE ? and "att1" in (?))))';

        $this->assertEquals($sql, $this->builder->toSql());
    }

    /** @test */
    public function searches_with_all_operators()
    {
        $input = [
            'search' => [
                'att1' => '=1',
                'att2' => '<1',
                'att3' => '<=1',
                'att4' => '>1',
                'att5' => '>=1',
                'att6' => '<>1;2',
                'att7' => '!<>1;2',
                'att8' => '!=1',
            ],
        ];

        $jsonQuery = new JsonQuery($this->builder, $input);
        $jsonQuery->search();

        $sql = 'select * from "test" where ((("att1" in (?))) and (("att2" < ?)) and (("att3" <= ?)) and (("att4" > ?)) and (("att5" >= ?)) and (("att6" between ? and ?)) and (("att7" not between ? and ?)) and (("att8" not in (?))))';

        $this->assertEquals($sql, $this->builder->toSql());
    }

    /** @test */
    public function searches_with_or_micro_operator()
    {
        $input = [
            'search' => [
                'id' => '=1||=2',
            ],
        ];

        $jsonQuery = new JsonQuery($this->builder, $input);
        $jsonQuery->search();

        $sql = 'select * from "test" where ((("id" in (?)) or ("id" in (?))))';

        $this->assertEquals($sql, $this->builder->toSql());
    }

    /** @test */
    public function searches_with_and_micro_operator()
    {
        $input = [
            'search' => [
                'id' => '=1&&=2',
            ],
        ];

        $jsonQuery = new JsonQuery($this->builder, $input);
        $jsonQuery->search();

        $sql = 'select * from "test" where ((("id" in (?) and "id" in (?))))';

        $this->assertEquals($sql, $this->builder->toSql());
    }

    /** @test */
    public function selects_only_given_attributes()
    {
        $input = [
            'returns' => ['id', 'other'],
        ];

        $jsonQuery = new JsonQuery($this->builder, $input);
        $jsonQuery->search();

        $sql = 'select "id", "other" from "test"';

        $this->assertEquals($sql, $this->builder->toSql());
    }

    /** @test */
    public function orders_by_attributes()
    {
        $input = [
            'order_by' => [
                'att1' => 'asc',
                'att2' => 'desc',
                'att3'
            ],
        ];

        $jsonQuery = new JsonQuery($this->builder, $input);
        $jsonQuery->search();

        $sql = 'select * from "test" order by "att1" asc, "att2" desc, "att3" asc';

        $this->assertEquals($sql, $this->builder->toSql());
    }

    /** @test */
    public function groups_by_attributes()
    {
        $input = [
            'group_by' => ['att1', 'att2'],
        ];

        $jsonQuery = new JsonQuery($this->builder, $input);
        $jsonQuery->search();

        $sql = 'select * from "test" group by "att1", "att2"';

        $this->assertEquals($sql, $this->builder->toSql());
    }

    /** @test */
    public function limits_and_offsets_results()
    {
        $input = [
            'limit'  => 5,
            'offset' => 10,
        ];

        $jsonQuery = new JsonQuery($this->builder, $input);
        $jsonQuery->search();

        $sql = 'select * from "test" limit 5 offset 10';

        $this->assertEquals($sql, $this->builder->toSql());
    }

    /** @test */
    public function counts_results()
    {
        $input = [
            'count' => true,
        ];

        $jsonQuery = new JsonQuery($this->builder, $input);
        $jsonQuery->search();

        $sql = 'select count(*) as count from "test"';

        $this->assertEquals($sql, $this->builder->toSql());
    }

    /** @test */
    public function uses_top_level_logical_operator_for_complex_queries()
    {
        $input = [
            'search' => [
                '||' => [
                    'att1' => '=1',
                    'att2' => '=1',
                ],
            ],
        ];

        $jsonQuery = new JsonQuery($this->builder, $input);
        $jsonQuery->search();

        $sql = 'select * from "test" where ((("att1" in (?))) or (("att2" in (?))))';

        $this->assertEquals($sql, $this->builder->toSql());
    }


    /** @test */
    public function uses_top_level_logical_operator_for_complex_recursive_queries()
    {
        $input = [
            'search' => [
                '&&' => [
                    '||'   => [
                        'att1' => '=1',
                        'att2' => '=1',
                    ],
                    'att3' => '=1',
                    'att4' => '=1',
                ],
            ],
        ];

        $jsonQuery = new JsonQuery($this->builder, $input);
        $jsonQuery->search();

        $sql = 'select * from "test" where ((("att1" in (?))) or (("att2" in (?))) and (("att3" in (?))) and (("att4" in (?))))';

        $this->assertEquals($sql, $this->builder->toSql());
    }

    /** @test */
    public function can_recurse_absurdly_deep()
    {
        $input = [
            'search' => [
                '||' => [
                    '&&'        => [
                        [
                            '||' => [
                                [
                                    'id'   => '=2||=3',
                                    'name' => '=foo',
                                ],
                                [
                                    'id'   => '=1',
                                    'name' => '=foo%&&=%bar',
                                ],
                            ],
                        ],
                        [
                            'we' => '=cool'
                        ],
                    ],
                    'love'      => '<3',
                    'recursion' => '=rrr',
                ],
            ],
        ];

        $jsonQuery = new JsonQuery($this->builder, $input);
        $jsonQuery->search();

        $sql = 'select * from "test" where ((((("id" in (?)) or ("id" in (?))) and (("name" in (?)))) or ((("id" in (?))) and (("name" LIKE ? and "name" LIKE ?)))) and ((("we" in (?)))) or (("love" < ?)) or (("recursion" in (?))))';

        $this->assertEquals($sql, $this->builder->toSql());
    }
}
