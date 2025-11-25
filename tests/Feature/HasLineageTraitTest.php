<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Lineage\Facades\Lineage;

describe('HasLineage Trait', function (): void {
    test('can add to lineage', function (): void {
        $user = user();

        $user->addToLineage('seller');

        expect(Lineage::isInHierarchy($user, 'seller'))->toBeTrue();
    });

    test('can add to lineage with parent', function (): void {
        $parent = user();
        $child = user();

        $parent->addToLineage('seller');
        $child->addToLineage('seller', $parent);

        expect(Lineage::getDirectParent($child, 'seller')->id)->toBe($parent->id);
    });

    test('can attach to lineage parent', function (): void {
        $parent = user();
        $child = user();

        $parent->addToLineage('seller');
        $child->addToLineage('seller');
        $child->attachToLineageParent($parent, 'seller');

        expect(Lineage::getDirectParent($child, 'seller')->id)->toBe($parent->id);
    });

    test('can detach from lineage parent', function (): void {
        [$parent, $child] = createHierarchyChain(2);

        $child->detachFromLineageParent('seller');

        expect(Lineage::isRoot($child, 'seller'))->toBeTrue();
    });

    test('can remove from lineage', function (): void {
        $user = user();
        $user->addToLineage('seller');

        $user->removeFromLineage('seller');

        expect(Lineage::isInHierarchy($user, 'seller'))->toBeFalse();
    });

    test('can move to lineage parent', function (): void {
        $root1 = user();
        $root2 = user();
        $child = user();

        $root1->addToLineage('seller');
        $root2->addToLineage('seller');
        $child->addToLineage('seller', $root1);

        $child->moveToLineageParent($root2, 'seller');

        expect(Lineage::getDirectParent($child, 'seller')->id)->toBe($root2->id);
    });

    test('can get lineage ancestors', function (): void {
        [$grandparent, $parent, $child] = createHierarchyChain(3);

        $ancestors = $child->getLineageAncestors('seller');

        expect($ancestors)->toHaveCount(2);
    });

    test('can get lineage descendants', function (): void {
        [$grandparent, $parent, $child] = createHierarchyChain(3);

        $descendants = $grandparent->getLineageDescendants('seller');

        expect($descendants)->toHaveCount(2);
    });

    test('can get lineage parent', function (): void {
        [$parent, $child] = createHierarchyChain(2);

        expect($child->getLineageParent('seller')->id)->toBe($parent->id);
    });

    test('can get lineage children', function (): void {
        [$parent, $child] = createHierarchyChain(2);

        $children = $parent->getLineageChildren('seller');

        expect($children)->toHaveCount(1);
        expect($children->first()->id)->toBe($child->id);
    });

    test('can check is lineage ancestor of', function (): void {
        [$parent, $child] = createHierarchyChain(2);

        expect($parent->isLineageAncestorOf($child, 'seller'))->toBeTrue();
        expect($child->isLineageAncestorOf($parent, 'seller'))->toBeFalse();
    });

    test('can check is lineage descendant of', function (): void {
        [$parent, $child] = createHierarchyChain(2);

        expect($child->isLineageDescendantOf($parent, 'seller'))->toBeTrue();
        expect($parent->isLineageDescendantOf($child, 'seller'))->toBeFalse();
    });

    test('can get lineage depth', function (): void {
        [$grandparent, $parent, $child] = createHierarchyChain(3);

        expect($grandparent->getLineageDepth('seller'))->toBe(0);
        expect($parent->getLineageDepth('seller'))->toBe(1);
        expect($child->getLineageDepth('seller'))->toBe(2);
    });

    test('can get lineage roots', function (): void {
        [$root, $parent, $child] = createHierarchyChain(3);

        $roots = $child->getLineageRoots('seller');

        expect($roots)->toHaveCount(1);
        expect($roots->first()->id)->toBe($root->id);
    });

    test('can build lineage tree', function (): void {
        [$root, $child] = createHierarchyChain(2);

        $tree = $root->buildLineageTree('seller');

        expect($tree['model']->id)->toBe($root->id);
        expect($tree['children'])->toHaveCount(1);
    });

    test('can get lineage path', function (): void {
        [$grandparent, $parent, $child] = createHierarchyChain(3);

        $path = $child->getLineagePath('seller');

        expect($path)->toHaveCount(3);
    });

    test('can check is in lineage', function (): void {
        $user = user();
        $user->addToLineage('seller');

        expect($user->isInLineage('seller'))->toBeTrue();
        expect($user->isInLineage('reseller'))->toBeFalse();
    });

    test('can check is lineage root', function (): void {
        [$parent, $child] = createHierarchyChain(2);

        expect($parent->isLineageRoot('seller'))->toBeTrue();
        expect($child->isLineageRoot('seller'))->toBeFalse();
    });

    test('can check is lineage leaf', function (): void {
        [$parent, $child] = createHierarchyChain(2);

        expect($child->isLineageLeaf('seller'))->toBeTrue();
        expect($parent->isLineageLeaf('seller'))->toBeFalse();
    });

    test('can get lineage siblings', function (): void {
        $parent = user();
        $child1 = user();
        $child2 = user();

        $parent->addToLineage('seller');
        $child1->addToLineage('seller', $parent);
        $child2->addToLineage('seller', $parent);

        $siblings = $child1->getLineageSiblings('seller');

        expect($siblings)->toHaveCount(1);
        expect($siblings->first()->id)->toBe($child2->id);
    });

    test('can access lineageAsAncestor relation', function (): void {
        [$parent, $child] = createHierarchyChain(2);

        $ancestorEntries = $parent->lineageAsAncestor;

        // Parent is ancestor of itself (depth 0) and child (depth 1)
        expect($ancestorEntries)->toHaveCount(2);
    });

    test('can access lineageAsDescendant relation', function (): void {
        [$parent, $child] = createHierarchyChain(2);

        $descendantEntries = $child->lineageAsDescendant;

        // Child is descendant of parent (depth 1) and itself (depth 0)
        expect($descendantEntries)->toHaveCount(2);
    });
});
