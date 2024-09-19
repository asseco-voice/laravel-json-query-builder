<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestModel extends Model
{
    protected $table = 'test';

    public function relationsOne(): HasMany
    {
        return $this->hasMany(TestRelationOneModel::class);
    }
}
