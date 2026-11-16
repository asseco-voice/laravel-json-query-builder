<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder\Tests\Unit\RequestParameters;

use Asseco\JsonQueryBuilder\JsonQuery;
use Asseco\JsonQueryBuilder\Tests\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;

/**
 * "!relation": {...} is WHERE NOT EXISTS with all inner conditions in ONE subquery, the negation of
 * the existing "relation": {...} sub-search. The other relation searches must not change.
 */
class NegatedRelationSearchTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Schema::create('nrs_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('channel_id');
        });

        Schema::create('nrs_user_variables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nrs_user_id');
            $table->string('key');
            $table->string('value');
        });

        NrsUser::query()->insert([
            ['id' => 1, 'name' => 'both-on-one-row', 'channel_id' => 81],
            ['id' => 2, 'name' => 'split-over-two-rows', 'channel_id' => 81],
            ['id' => 3, 'name' => 'no-variables', 'channel_id' => 81],
            ['id' => 4, 'name' => 'other-channel', 'channel_id' => 7],
        ]);

        NrsUserVariable::query()->insert([
            ['nrs_user_id' => 1, 'key' => 'k489', 'value' => '123'],
            ['nrs_user_id' => 2, 'key' => 'k489', 'value' => '999'],
            ['nrs_user_id' => 2, 'key' => 'other', 'value' => '123'],
        ]);
    }

    #[Test]
    public function negated_sub_search_keeps_all_conditions_in_one_not_exists()
    {
        // users of channel 81 without a variable that is k489 AND 123 on the same row (issue #48)
        $names = $this->names([
            'channel_id'          => '=81',
            '!nrs_user_variables' => ['search' => ['key' => '=k489', 'value' => '=123']],
        ]);

        $this->assertSame(['split-over-two-rows', 'no-variables'], $names);
    }

    #[Test]
    public function sub_search_is_unchanged()
    {
        $this->assertSame(['both-on-one-row'], $this->names([
            'nrs_user_variables' => ['search' => ['key' => '=k489', 'value' => '=123']],
        ]));
    }

    #[Test]
    public function negated_flat_relation_search_is_unchanged()
    {
        // user 2 has a row with value 123 (key "other"), so it is excluded as well
        $this->assertSame(['no-variables', 'other-channel'], $this->names([
            '!nrs_user_variables.value' => '=123',
        ]));
    }

    #[Test]
    public function flat_relation_search_is_unchanged()
    {
        $this->assertSame(['both-on-one-row', 'split-over-two-rows'], $this->names([
            'nrs_user_variables.key' => '=k489',
        ]));
    }

    #[Test]
    public function negated_sub_search_inside_a_top_level_or()
    {
        $names = $this->names([
            '||' => [
                ['channel_id' => '=7'],
                ['!nrs_user_variables' => ['search' => ['key' => '=k489']]],
            ],
        ]);

        $this->assertSame(['no-variables', 'other-channel'], $names);
    }

    private function names(array $search): array
    {
        $builder = app(Builder::class);
        $builder->setModel(new NrsUser());

        (new JsonQuery($builder, ['search' => $search]))->search();

        return $builder->orderBy('id')->pluck('name')->all();
    }
}

class NrsUser extends Model
{
    protected $table = 'nrs_users';
    public $timestamps = false;

    public function nrsUserVariables(): HasMany
    {
        return $this->hasMany(NrsUserVariable::class, 'nrs_user_id');
    }
}

class NrsUserVariable extends Model
{
    protected $table = 'nrs_user_variables';
    public $timestamps = false;
}
