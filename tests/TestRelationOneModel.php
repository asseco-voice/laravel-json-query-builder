<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestRelationOneModel extends Model
{
    protected $table = 'test_relation_one';

    public function testModel(): BelongsTo
    {
        return $this->belongsTo(TestModel::class);
    }
}
