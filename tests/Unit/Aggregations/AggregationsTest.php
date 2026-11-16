<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Tests\Unit\Aggregations;

use Asseco\JsonQueryBuilder\Aggregations\AggregateExpression;
use Asseco\JsonQueryBuilder\Exceptions\JsonQueryBuilderException;
use Asseco\JsonQueryBuilder\JsonQuery;
use Asseco\JsonQueryBuilder\Tests\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class AggregationsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('amount');
            $table->string('secret')->nullable();
            $table->dateTime('sold_at');
        });

        SaleModel::query()->insert([
            ['name' => 'a', 'amount' => 10, 'sold_at' => '2025-01-15 10:00:00'],
            ['name' => 'a', 'amount' => 20, 'sold_at' => '2025-01-20 10:00:00'],
            ['name' => 'b', 'amount' => 5, 'sold_at' => '2025-02-03 10:00:00'],
            ['name' => 'c', 'amount' => 1, 'sold_at' => '2026-02-03 10:00:00'],
        ]);

        config(['asseco-json-query-builder.model_options' => [
            SaleModel::class => ['aggregations' => true, 'forbidden_columns' => ['secret']],
        ]]);
    }

    public static function sqlPerDriver(): array
    {
        return [
            'count all'            => ['count:*', 'pgsql', 'COUNT(*) as "count"'],
            'sum'                  => ['sum:amount', 'pgsql', 'SUM("amount") as "sum_amount"'],
            'count distinct'       => ['count:distinct:name', 'pgsql', 'COUNT(DISTINCT "name") as "count_distinct_name"'],
            'pgsql year'           => ['year:sold_at', 'pgsql', 'EXTRACT(YEAR FROM "sold_at") as "year_sold_at"'],
            'pgsql min of month'   => ['min:month:sold_at', 'pgsql', 'MIN(EXTRACT(MONTH FROM "sold_at")) as "min_month_sold_at"'],
            'mysql day'            => ['day:sold_at', 'mysql', 'DAY("sold_at") as "day_sold_at"'],
            'sqlite month'         => ['month:sold_at', 'sqlite', 'CAST(strftime(\'%m\', "sold_at") AS INTEGER) as "month_sold_at"'],
            'sqlsrv year'          => ['year:sold_at', 'sqlsrv', 'DATEPART(year, "sold_at") as "year_sold_at"'],
        ];
    }

    #[Test]
    #[DataProvider('sqlPerDriver')]
    public function builds_sql(string $argument, string $driver, string $expected)
    {
        $wrap = fn ($identifier) => '"' . $identifier . '"';

        $this->assertSame($expected, AggregateExpression::toSql($argument, ['name', 'amount', 'sold_at'], $driver, $wrap));
    }

    public static function invalidArguments(): array
    {
        return [
            'injection through the column'  => ['sum:x"), (select password from users) as y --'],
            'unknown column'                => ['sum:nope'],
            'forbidden column'              => ['count:secret'],
            'unknown function'              => ['upper:name'],
            'function as column'            => ['sum:count'],
            'star with another aggregate'   => ['sum:*'],
            'star with a date part'         => ['year:*'],
            'distinct without count'        => ['sum:distinct:amount'],
            'two aggregates'                => ['min:max:amount'],
            'date part before aggregate'    => ['year:count:sold_at'],
            'no function'                   => [':name'],
        ];
    }

    #[Test]
    #[DataProvider('invalidArguments')]
    public function rejects_invalid_arguments(string $argument)
    {
        $this->expectException(JsonQueryBuilderException::class);

        $this->runSearch(['returns' => [$argument]])->get();
    }

    #[Test]
    public function without_opt_in_the_argument_is_a_plain_column_as_before()
    {
        config(['asseco-json-query-builder.model_options' => []]);

        $sql = $this->runSearch(['returns' => ['count:*']])->toSql();

        $this->assertStringNotContainsString('COUNT(', $sql);
        $this->assertStringContainsString('"count:*"', $sql);
    }

    #[Test]
    public function plain_returns_still_work_without_enabling_it()
    {
        config(['asseco-json-query-builder.model_options' => []]);

        $this->assertSame(['a', 'a', 'b', 'c'], $this->runSearch(['returns' => ['name']])->pluck('name')->all());
    }

    #[Test]
    public function groups_by_year_and_month()
    {
        $rows = $this->runSearch([
            'returns'  => ['year:sold_at', 'month:sold_at', 'count:*', 'sum:amount'],
            'group_by' => ['year_sold_at', 'month_sold_at'],
            'order_by' => ['year_sold_at', 'month_sold_at'],
        ])->toBase()->get()->map(fn ($row) => (array) $row)->all();

        $this->assertEquals([
            ['year_sold_at' => 2025, 'month_sold_at' => 1, 'count' => 2, 'sum_amount' => 30],
            ['year_sold_at' => 2025, 'month_sold_at' => 2, 'count' => 1, 'sum_amount' => 5],
            ['year_sold_at' => 2026, 'month_sold_at' => 2, 'count' => 1, 'sum_amount' => 1],
        ], $rows);
    }

    #[Test]
    public function counts_distinct_values()
    {
        $row = (array) $this->runSearch(['returns' => ['count:distinct:name']])->toBase()->first();

        $this->assertEquals(['count_distinct_name' => 3], $row);
    }

    private function runSearch(array $input): Builder
    {
        $builder = app(Builder::class);
        $builder->setModel(new SaleModel());

        (new JsonQuery($builder, $input))->search();

        return $builder;
    }
}

class SaleModel extends Model
{
    protected $table = 'sales';
    public $timestamps = false;
}
