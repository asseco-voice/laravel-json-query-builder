<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Tests\Unit\Config;

use Asseco\JsonQueryBuilder\Config\ModelConfig;
use Asseco\JsonQueryBuilder\Exceptions\JsonQueryBuilderException;
use Asseco\JsonQueryBuilder\JsonQuery;
use Asseco\JsonQueryBuilder\Tests\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Column types must keep their Laravel 10 (Doctrine) names on Laravel 11+, where
 * getColumnType() returns the database's own type names instead.
 */
class ColumnTypeNormalizationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Schema::create('type_test', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('body');
            $table->boolean('active');
        });
    }

    public static function nativeTypes(): array
    {
        return [
            'pgsql varchar'    => ['pgsql', 'varchar', 'string'],
            'pgsql bpchar'     => ['pgsql', 'bpchar', 'string'],
            'pgsql text'       => ['pgsql', 'text', 'text'],
            'pgsql bool'       => ['pgsql', 'bool', 'boolean'],
            'pgsql timestamp'  => ['pgsql', 'timestamp', 'timestamp'],
            'pgsql uuid'       => ['pgsql', 'uuid', 'uuid'],
            'mysql varchar'    => ['mysql', 'varchar', 'string'],
            'mysql tinyint'    => ['mysql', 'tinyint', 'boolean'],
            'mariadb char'     => ['mariadb', 'char', 'string'],
            'sqlite varchar'   => ['sqlite', 'varchar', 'string'],
            'sqlite tinyint'   => ['sqlite', 'tinyint', 'boolean'],
            'upper case'       => ['pgsql', 'VARCHAR', 'string'],
            'doctrine string'  => ['pgsql', 'string', 'string'],
            'doctrine boolean' => ['pgsql', 'boolean', 'boolean'],
            'unknown driver'   => ['sqlsrv', 'varchar', 'varchar'],
        ];
    }

    #[Test]
    #[DataProvider('nativeTypes')]
    public function normalizes_native_type_names(string $driver, string $type, string $expected)
    {
        $this->assertSame($expected, ModelConfig::normalizeColumnType($driver, $type));
    }

    #[Test]
    public function model_columns_have_doctrine_type_names()
    {
        $columns = (new ModelConfig(new TypeTestModel()))->getModelColumns();

        $this->assertSame('string', $columns['name']);
        $this->assertSame('text', $columns['body']);
        $this->assertSame('boolean', $columns['active']);
    }

    #[Test]
    public function single_value_on_string_column_is_matched_with_like()
    {
        TypeTestModel::query()->insert([
            ['name' => 'Alice', 'body' => 'x', 'active' => true],
            ['name' => 'Bob', 'body' => 'y', 'active' => false],
        ]);

        $builder = $this->search(['name' => '=alice']);

        $this->assertStringContainsStringIgnoringCase('like', $builder->toSql());
        $this->assertSame(['Alice'], $builder->pluck('name')->all());
    }

    #[Test]
    public function boolean_column_rejects_a_non_boolean_value()
    {
        $this->expectException(JsonQueryBuilderException::class);

        $this->search(['active' => '=abc']);
    }

    private function search(array $search): Builder
    {
        $builder = app(Builder::class);
        $builder->setModel(new TypeTestModel());

        (new JsonQuery($builder, ['search' => $search]))->search();

        return $builder;
    }
}

class TypeTestModel extends Model
{
    protected $table = 'type_test';
    public $timestamps = false;
}
