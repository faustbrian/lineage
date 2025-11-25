<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Lineage\Facades\Lineage;

describe('Hierarchy Modification Operations', function (): void {
    test('can detach from parent', function (): void {
        [$parent, $child] = createHierarchyChain(2);

        Lineage::detachFromParent($child, 'seller');

        expect(Lineage::isInHierarchy($child, 'seller'))->toBeTrue();
        expect(Lineage::isRoot($child, 'seller'))->toBeTrue();
        expect(Lineage::getDirectParent($child, 'seller'))->toBeNull();
    });

    test('can remove from hierarchy completely', function (): void {
        [$parent, $child, $grandchild] = createHierarchyChain(3);

        Lineage::removeFromHierarchy($child, 'seller');

        expect(Lineage::isInHierarchy($child, 'seller'))->toBeFalse();
        // Grandchild should also lose its ancestor paths through child
        expect(Lineage::isDescendantOf($grandchild, $parent, 'seller'))->toBeFalse();
    });

    test('can move to new parent', function (): void {
        $root1 = user();
        $root2 = user();
        $child = user();

        Lineage::addToHierarchy($root1, 'seller');
        Lineage::addToHierarchy($root2, 'seller');
        Lineage::addToHierarchy($child, 'seller', $root1);

        Lineage::moveToParent($child, $root2, 'seller');

        expect(Lineage::getDirectParent($child, 'seller')->id)->toBe($root2->id);
        expect(Lineage::isDescendantOf($child, $root1, 'seller'))->toBeFalse();
        expect(Lineage::isDescendantOf($child, $root2, 'seller'))->toBeTrue();
    });

    test('can move to become root', function (): void {
        [$parent, $child] = createHierarchyChain(2);

        Lineage::moveToParent($child, null, 'seller');

        expect(Lineage::isRoot($child, 'seller'))->toBeTrue();
        expect(Lineage::isDescendantOf($child, $parent, 'seller'))->toBeFalse();
    });

    test('can attach to parent', function (): void {
        $parent = user();
        $child = user();

        Lineage::addToHierarchy($parent, 'seller');
        Lineage::addToHierarchy($child, 'seller');

        expect(Lineage::isRoot($child, 'seller'))->toBeTrue();

        Lineage::attachToParent($child, $parent, 'seller');

        expect(Lineage::isRoot($child, 'seller'))->toBeFalse();
        expect(Lineage::getDirectParent($child, 'seller')->id)->toBe($parent->id);
    });

    test('can attach model not in hierarchy to parent', function (): void {
        $parent = user();
        $child = user();

        // Only add parent to hierarchy, NOT child
        Lineage::addToHierarchy($parent, 'seller');

        // Child is not in any hierarchy yet
        expect(Lineage::isInHierarchy($child, 'seller'))->toBeFalse();

        // Attach child directly to parent (triggers ensureSelfReference)
        Lineage::attachToParent($child, $parent, 'seller');

        expect(Lineage::isInHierarchy($child, 'seller'))->toBeTrue();
        expect(Lineage::getDirectParent($child, 'seller')->id)->toBe($parent->id);
    });
});

describe('Move with Descendants', function (): void {
    test('moving subtree preserves descendant relationships', function (): void {
        $root1 = user();
        $root2 = user();
        $parent = user();
        $child = user();
        $grandchild = user();

        Lineage::addToHierarchy($root1, 'seller');
        Lineage::addToHierarchy($root2, 'seller');
        Lineage::addToHierarchy($parent, 'seller', $root1);
        Lineage::addToHierarchy($child, 'seller', $parent);
        Lineage::addToHierarchy($grandchild, 'seller', $child);

        // Move the subtree from root1 to root2
        Lineage::moveToParent($parent, $root2, 'seller');

        // Verify new ancestry
        expect(Lineage::isDescendantOf($parent, $root2, 'seller'))->toBeTrue();
        expect(Lineage::isDescendantOf($child, $root2, 'seller'))->toBeTrue();
        expect(Lineage::isDescendantOf($grandchild, $root2, 'seller'))->toBeTrue();

        // Verify old ancestry is gone
        expect(Lineage::isDescendantOf($parent, $root1, 'seller'))->toBeFalse();
        expect(Lineage::isDescendantOf($child, $root1, 'seller'))->toBeFalse();
        expect(Lineage::isDescendantOf($grandchild, $root1, 'seller'))->toBeFalse();

        // Verify internal subtree relationships preserved
        expect(Lineage::getDirectParent($child, 'seller')->id)->toBe($parent->id);
        expect(Lineage::getDirectParent($grandchild, 'seller')->id)->toBe($child->id);
    });
});
