<?php

declare (strict_types = 1);
namespace Asseco\JsonQueryBuilder\Tests\Unit\SQLProviders;

use Asseco\JsonQueryBuilder\SQLProviders\SQLFunctions;
use \Asseco\JsonQueryBuilder\Tests\TestCase;

class SQLFunctionsTest extends TestCase
{

    public SQLFunctions $functions;

    public function setUp(): void
    {
        parent::setUp();

        $this->functions = new SQLFunctions();
    }
    public function test_it_returns_the_right_functions_query()
    {
        foreach (SQLFunctions::DB_FUNCTIONS as $fn) {
            $query = $this->functions::{$fn}('column');
            $this->assertEquals("{$fn}(column)", $query);
        }
    }

    public function test_it_throws_an_exception_if_an_invalid_function_is_given()
    {
        $this->expectException(\Asseco\JsonQueryBuilder\Exceptions\JsonQueryBuilderException::class);
        $this->functions::invalidFunction('id');
    }

    public function test_it_validate_sql_functions()
    {
        foreach (SQLFunctions::DB_FUNCTIONS as $fn) {
            $this->assertNull($this->functions::validateArgument($fn . ':column'));
        }
    }

    public function test_it_validate_nested_functions_validation()
    {
        $this->assertNull($this->functions::validateArgument('avg:year:column'));
    }

    public function test_it_bypass_when_no_function_is_given()
    {
        $this->assertNull($this->functions::validateArgument('column'));
    }

    public function test_it_throws_an_exception_if_an_invalid_argument_is_given()
    {
        $this->expectException(\Asseco\JsonQueryBuilder\Exceptions\JsonQueryBuilderException::class);
        $this->functions::validateArgument('invalid:column');
    }

    public function test_it_throws_an_exception_if_an_invalid_column_is_given()
    {
        $this->expectException(\Asseco\JsonQueryBuilder\Exceptions\JsonQueryBuilderException::class);
        $this->functions::validateArgument("avg:'this--sql-scripting-is-invalid'");
    }

    public function test_it_throws_an_exception_if_no_column_is_given()
    {
        $this->expectException(\Asseco\JsonQueryBuilder\Exceptions\JsonQueryBuilderException::class);
        $this->functions::validateArgument('sum');
    }
    public function test_it_throws_an_exception_if_no_column_when_nested_is_given()
    {
        $this->expectException(\Asseco\JsonQueryBuilder\Exceptions\JsonQueryBuilderException::class);
        $this->functions::validateArgument('sum:avg');
    }
}
