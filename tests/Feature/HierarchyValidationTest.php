<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Lineage\Exceptions\CircularReferenceException;
use Cline\Lineage\Exceptions\MaxDepthExceededException;
use Cline\Lineage\Facades\Lineage;

describe('Hierarchy Validation', function (): void {
    test('prevents circular reference when attaching', function (): void {
        [$grandparent, $parent, $child] = createHierarchyChain(3);

        expect(fn () => Lineage::attachToParent($grandparent, $child, 'seller'))
            ->toThrow(CircularReferenceException::class);
    });

    test('prevents circular reference when moving', function (): void {
        [$grandparent, $parent, $child] = createHierarchyChain(3);

        expect(fn () => Lineage::moveToParent($grandparent, $child, 'seller'))
            ->toThrow(CircularReferenceException::class);
    });

    test('prevents exceeding max depth', function (): void {
        config()->set('lineage.max_depth', 3);

        // Create a chain at max depth
        $users = createHierarchyChain(4); // This creates depth 3 (0, 1, 2, 3)

        // Try to add another level
        $newUser = user();

        expect(fn () => Lineage::addToHierarchy($newUser, 'seller', end($users)))
            ->toThrow(MaxDepthExceededException::class);
    });

    test('allows unlimited depth when max_depth is null', function (): void {
        config()->set('lineage.max_depth');

        // Create a deep chain
        $users = createHierarchyChain(15);

        expect(Lineage::getDepth(end($users), 'seller'))->toBe(14);
    });
});
