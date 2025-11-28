<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Lineage\Facades\Lineage;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Tests\Fixtures\Order;

describe('HasLineageSnapshots Trait', function (): void {
    describe('snapshotLineage', function (): void {
        test('creates snapshots for full hierarchy chain', function (): void {
            // Create: CEO -> VP -> Manager (3-level hierarchy)
            [$ceo, $vp, $manager] = createHierarchyChain(3);
            $shipment = order(['reference' => 'TEST-001']);

            // Snapshot the manager's seller hierarchy
            $shipment->snapshotLineage($manager, 'seller');

            // Should have 3 snapshots (depths 0, 1, 2)
            $snapshots = $shipment->getLineageSnapshots('seller');
            expect($snapshots)->toHaveCount(3);

            // Verify depth order (0 = direct, 1 = parent, 2 = grandparent)
            expect($snapshots[0]->depth)->toBe(0);
            expect($snapshots[0]->ancestor_id)->toEqual($manager->id);

            expect($snapshots[1]->depth)->toBe(1);
            expect($snapshots[1]->ancestor_id)->toEqual($vp->id);

            expect($snapshots[2]->depth)->toBe(2);
            expect($snapshots[2]->ancestor_id)->toEqual($ceo->id);
        });

        test('creates single snapshot for root node', function (): void {
            $root = user();
            Lineage::addToHierarchy($root, 'seller');
            $shipment = order();

            $shipment->snapshotLineage($root, 'seller');

            $snapshots = $shipment->getLineageSnapshots('seller');
            expect($snapshots)->toHaveCount(1);
            expect($snapshots[0]->depth)->toBe(0);
            expect($snapshots[0]->ancestor_id)->toEqual($root->id);
        });

        test('replaces existing snapshots when called again', function (): void {
            [$parent1, $child] = createHierarchyChain(2);
            $shipment = order();

            // First snapshot
            $shipment->snapshotLineage($child, 'seller');

            expect($shipment->getLineageSnapshots('seller'))->toHaveCount(2);

            // Move child to different parent
            $parent2 = user();
            Lineage::addToHierarchy($parent2, 'seller');
            Lineage::moveToParent($child, $parent2, 'seller');

            // Snapshot again - should replace
            $shipment->snapshotLineage($child, 'seller');

            $snapshots = $shipment->getLineageSnapshots('seller');
            expect($snapshots)->toHaveCount(2);
            expect($snapshots[1]->ancestor_id)->toEqual($parent2->id);
        });

        test('creates snapshots for different hierarchy types independently', function (): void {
            $seller = user();
            $reseller = user();
            Lineage::addToHierarchy($seller, 'seller');
            Lineage::addToHierarchy($reseller, 'reseller');

            $shipment = order();
            $shipment->snapshotLineage($seller, 'seller');
            $shipment->snapshotLineage($reseller, 'reseller');

            expect($shipment->getLineageSnapshots('seller'))->toHaveCount(1);
            expect($shipment->getLineageSnapshots('reseller'))->toHaveCount(1);
        });

        test('handles deep hierarchies', function (): void {
            // Create 5-level hierarchy
            $users = createHierarchyChain(5);
            $shipment = order();

            $shipment->snapshotLineage($users[4], 'seller');

            $snapshots = $shipment->getLineageSnapshots('seller');
            expect($snapshots)->toHaveCount(5);

            // Verify all depths are correct
            foreach ($snapshots as $index => $snapshot) {
                expect($snapshot->depth)->toBe($index);
            }
        });
    });

    describe('getLineageSnapshots', function (): void {
        test('returns empty collection when no snapshots exist', function (): void {
            $shipment = order();

            $snapshots = $shipment->getLineageSnapshots('seller');

            expect($snapshots)->toBeEmpty();
        });

        test('returns snapshots ordered by depth', function (): void {
            [$grandparent, $parent, $child] = createHierarchyChain(3);
            $shipment = order();
            $shipment->snapshotLineage($child, 'seller');

            $snapshots = $shipment->getLineageSnapshots('seller');

            expect($snapshots[0]->depth)->toBeLessThan($snapshots[1]->depth);
            expect($snapshots[1]->depth)->toBeLessThan($snapshots[2]->depth);
        });

        test('only returns snapshots for specified type', function (): void {
            $seller = user();
            $reseller = user();
            Lineage::addToHierarchy($seller, 'seller');
            Lineage::addToHierarchy($reseller, 'reseller');

            $shipment = order();
            $shipment->snapshotLineage($seller, 'seller');
            $shipment->snapshotLineage($reseller, 'reseller');

            $sellerSnapshots = $shipment->getLineageSnapshots('seller');
            $resellerSnapshots = $shipment->getLineageSnapshots('reseller');

            expect($sellerSnapshots)->toHaveCount(1);
            expect($resellerSnapshots)->toHaveCount(1);
            expect($sellerSnapshots[0]->ancestor_id)->toEqual($seller->id);
            expect($resellerSnapshots[0]->ancestor_id)->toEqual($reseller->id);
        });
    });

    describe('hasLineageSnapshots', function (): void {
        test('returns false when no snapshots exist', function (): void {
            $shipment = order();

            expect($shipment->hasLineageSnapshots('seller'))->toBeFalse();
        });

        test('returns true when snapshots exist', function (): void {
            $user = user();
            Lineage::addToHierarchy($user, 'seller');
            $shipment = order();
            $shipment->snapshotLineage($user, 'seller');

            expect($shipment->hasLineageSnapshots('seller'))->toBeTrue();
        });

        test('returns false for different type', function (): void {
            $user = user();
            Lineage::addToHierarchy($user, 'seller');
            $shipment = order();
            $shipment->snapshotLineage($user, 'seller');

            expect($shipment->hasLineageSnapshots('reseller'))->toBeFalse();
        });
    });

    describe('clearLineageSnapshots', function (): void {
        test('removes all snapshots for a type', function (): void {
            [$parent, $child] = createHierarchyChain(2);
            $shipment = order();
            $shipment->snapshotLineage($child, 'seller');

            expect($shipment->hasLineageSnapshots('seller'))->toBeTrue();

            $shipment->clearLineageSnapshots('seller');

            expect($shipment->hasLineageSnapshots('seller'))->toBeFalse();
            expect($shipment->getLineageSnapshots('seller'))->toBeEmpty();
        });

        test('only clears specified type', function (): void {
            $seller = user();
            $reseller = user();
            Lineage::addToHierarchy($seller, 'seller');
            Lineage::addToHierarchy($reseller, 'reseller');

            $shipment = order();
            $shipment->snapshotLineage($seller, 'seller');
            $shipment->snapshotLineage($reseller, 'reseller');

            $shipment->clearLineageSnapshots('seller');

            expect($shipment->hasLineageSnapshots('seller'))->toBeFalse();
            expect($shipment->hasLineageSnapshots('reseller'))->toBeTrue();
        });
    });

    describe('getLineageSnapshotAtDepth', function (): void {
        test('returns snapshot at specific depth', function (): void {
            [$grandparent, $parent, $child] = createHierarchyChain(3);
            $shipment = order();
            $shipment->snapshotLineage($child, 'seller');

            $snapshot = $shipment->getLineageSnapshotAtDepth('seller', 1);

            expect($snapshot)->not->toBeNull();
            expect($snapshot->ancestor_id)->toEqual($parent->id);
        });

        test('returns null for non-existent depth', function (): void {
            $user = user();
            Lineage::addToHierarchy($user, 'seller');
            $shipment = order();
            $shipment->snapshotLineage($user, 'seller');

            $snapshot = $shipment->getLineageSnapshotAtDepth('seller', 5);

            expect($snapshot)->toBeNull();
        });
    });

    describe('getDirectLineageSnapshot', function (): void {
        test('returns depth 0 snapshot', function (): void {
            [$parent, $child] = createHierarchyChain(2);
            $shipment = order();
            $shipment->snapshotLineage($child, 'seller');

            $direct = $shipment->getDirectLineageSnapshot('seller');

            expect($direct)->not->toBeNull();
            expect($direct->depth)->toBe(0);
            expect($direct->ancestor_id)->toEqual($child->id);
        });

        test('returns null when no snapshots exist', function (): void {
            $shipment = order();

            expect($shipment->getDirectLineageSnapshot('seller'))->toBeNull();
        });
    });

    describe('lineageSnapshots relation', function (): void {
        test('returns morphMany relation', function (): void {
            $shipment = order();

            expect($shipment->lineageSnapshots())->toBeInstanceOf(
                MorphMany::class,
            );
        });

        test('can eager load snapshots', function (): void {
            $user = user();
            Lineage::addToHierarchy($user, 'seller');
            $shipment = order();
            $shipment->snapshotLineage($user, 'seller');

            $loaded = Order::with('lineageSnapshots')->find($shipment->id);

            expect($loaded->lineageSnapshots)->toHaveCount(1);
        });
    });

    describe('snapshot preservation', function (): void {
        test('snapshots are preserved when hierarchy changes', function (): void {
            // Create hierarchy and snapshot
            [$originalParent, $child] = createHierarchyChain(2);
            $shipment = order();
            $shipment->snapshotLineage($child, 'seller');

            // Verify original snapshot
            $originalSnapshot = $shipment->getLineageSnapshots('seller');
            expect($originalSnapshot[1]->ancestor_id)->toEqual($originalParent->id);

            // Move child to new parent
            $newParent = user();
            Lineage::addToHierarchy($newParent, 'seller');
            Lineage::moveToParent($child, $newParent, 'seller');

            // Refresh shipment from DB and verify snapshot unchanged
            $shipment->refresh();
            $preservedSnapshot = $shipment->getLineageSnapshots('seller');

            expect($preservedSnapshot[1]->ancestor_id)->toEqual($originalParent->id);
        });

        test('snapshots are preserved when ancestor is deleted from hierarchy', function (): void {
            [$grandparent, $parent, $child] = createHierarchyChain(3);
            $shipment = order();
            $shipment->snapshotLineage($child, 'seller');

            // Remove parent from hierarchy (orphans child)
            Lineage::removeFromHierarchy($parent, 'seller');

            // Snapshot should still reference the original parent
            $snapshots = $shipment->getLineageSnapshots('seller');
            expect($snapshots[1]->ancestor_id)->toEqual($parent->id);
        });
    });
});
