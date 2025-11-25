<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Lineage\Facades\Lineage;
use Illuminate\Database\Eloquent\Model;
use Tests\Fixtures\User;
use Tests\TestCase;

pest()->extend(TestCase::class)->in(__DIR__);

/**
 * Create a test user.
 */
function user(array $attributes = []): User
{
    return User::query()->create($attributes);
}

/**
 * Create a hierarchy chain of users.
 *
 * @param  int              $count Number of users in the chain
 * @param  string           $type  Hierarchy type
 * @return array<int, User>
 */
function createHierarchyChain(int $count, string $type = 'seller'): array
{
    $users = [];
    $parent = null;

    for ($i = 0; $i < $count; ++$i) {
        $user = user();
        Lineage::addToHierarchy($user, $type, $parent);
        $users[] = $user;
        $parent = $user;
    }

    return $users;
}

/**
 * Get the morph key value for a model.
 */
function getModelKey(Model $model): mixed
{
    $morphType = config('lineage.ancestor_morph_type', 'morph');

    return match ($morphType) {
        'uuidMorph' => $model->uuid,
        'ulidMorph' => $model->ulid,
        default => $model->id,
    };
}
