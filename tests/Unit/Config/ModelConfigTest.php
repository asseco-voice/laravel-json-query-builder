<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Tests\Unit\Config;

use Asseco\JsonQueryBuilder\Config\ModelConfig;
use Asseco\JsonQueryBuilder\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Mockery;

class ModelConfigTest extends TestCase
{
    protected Model $model;

    public function setUp(): void
    {
        parent::setUp();

        $this->model = Mockery::mock(Model::class);
    }

    /** @test */
    public function model_has_config()
    {
        config(['asseco-json-query-builder.model_options' => [
            get_class($this->model) => ['random_config' => '123']
        ]]);

        $modelConfig = new ModelConfig($this->model);

        $this->assertTrue($modelConfig->hasConfig());
    }

    /** @test */
    public function has_returns_config_set()
    {
        config(['asseco-json-query-builder.model_options' => [
            get_class($this->model) => ['returns' => '123']
        ]]);

        $modelConfig = new ModelConfig($this->model);

        $this->assertEquals(['123'], $modelConfig->getReturns());
    }

    /** @test */
    public function has_default_returns_config_set()
    {
        $modelConfig = new ModelConfig($this->model);

        $this->assertEquals(['*'], $modelConfig->getReturns());
    }

    /** @test */
    public function has_order_by_config_set()
    {
        config(['asseco-json-query-builder.model_options' => [
            get_class($this->model) => [
                'order_by' => [
                    'attribute' => 'asc',
                ]
            ]
        ]]);

        $modelConfig = new ModelConfig($this->model);

        $this->assertEquals(['attribute=asc'], $modelConfig->getOrderBy());
    }

    /** @test */
    public function has_default_order_by_config_set()
    {
        $modelConfig = new ModelConfig($this->model);

        $this->assertEquals([], $modelConfig->getOrderBy());
    }


}
