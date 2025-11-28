<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Lineage\Database\HierarchySnapshot;
use Cline\Lineage\Facades\Lineage;
use Tests\Fixtures\Order;

describe('HierarchySnapshot Model', function (): void {
    describe('attributes', function (): void {
        test('has correct fillable attributes', function (): void {
            $snapshot = new HierarchySnapshot();

            expect($snapshot->getFillable())->toBe([
                'context_type',
                'context_id',
                'type',
                'depth',
                'ancestor_id',
            ]);
        });

        test('casts depth to integer', function (): void {
            $user = user();
            Lineage::addToHierarchy($user, 'seller');
            $shipment = order();
            $shipment->snapshotLineage($user, 'seller');

            $snapshot = $shipment->lineageSnapshots()->first();

            expect($snapshot->depth)->toBeInt();
        });
    });

    describe('context relation', function (): void {
        test('returns the context model', function (): void {
            $user = user();
            Lineage::addToHierarchy($user, 'seller');
            $shipment = order(['reference' => 'TEST-001']);
            $shipment->snapshotLineage($user, 'seller');

            $snapshot = HierarchySnapshot::query()->first();

            expect($snapshot->context)->toBeInstanceOf(Order::class);
            expect($snapshot->context->id)->toBe($shipment->id);
        });
    });

    describe('scopes', function (): void {
        beforeEach(function (): void {
            // Create two shipments with different hierarchies
            $seller1 = user();
            $seller2 = user();
            $reseller = user();

            Lineage::addToHierarchy($seller1, 'seller');
            Lineage::addToHierarchy($seller2, 'seller');
            Lineage::addToHierarchy($reseller, 'reseller');

            $this->shipment1 = order(['reference' => 'SHIP-001']);
            $this->shipment2 = order(['reference' => 'SHIP-002']);

            $this->shipment1->snapshotLineage($seller1, 'seller');
            $this->shipment1->snapshotLineage($reseller, 'reseller');

            $this->shipment2->snapshotLineage($seller2, 'seller');
        });

        test('scopeForContext filters by context model', function (): void {
            $snapshots = HierarchySnapshot::query()
                ->forContext($this->shipment1)
                ->get();

            expect($snapshots)->toHaveCount(2); // seller + reseller
            expect($snapshots->every(fn ($s): bool => $s->context_id === $this->shipment1->id))->toBeTrue();
        });

        test('scopeOfType filters by hierarchy type', function (): void {
            $sellerSnapshots = HierarchySnapshot::query()
                ->ofType('seller')
                ->get();

            expect($sellerSnapshots)->toHaveCount(2); // one per shipment
            expect($sellerSnapshots->every(fn ($s): bool => $s->type === 'seller'))->toBeTrue();
        });

        test('scopeOrderedByDepth orders by depth ascending', function (): void {
            [$grandparent, $parent, $child] = createHierarchyChain(3);
            $shipment = order();
            $shipment->snapshotLineage($child, 'seller');

            $snapshots = HierarchySnapshot::query()
                ->forContext($shipment)
                ->ofType('seller')
                ->orderedByDepth()
                ->get();

            expect($snapshots[0]->depth)->toBe(0);
            expect($snapshots[1]->depth)->toBe(1);
            expect($snapshots[2]->depth)->toBe(2);
        });

        test('scopes can be chained', function (): void {
            $snapshots = HierarchySnapshot::query()
                ->forContext($this->shipment1)
                ->ofType('seller')
                ->orderedByDepth()
                ->get();

            expect($snapshots)->toHaveCount(1);
            expect($snapshots[0]->type)->toBe('seller');
        });
    });

    describe('table configuration', function (): void {
        test('uses configured table name', function (): void {
            $snapshot = new HierarchySnapshot();

            expect($snapshot->getTable())->toBe(
                config('lineage.snapshots.table_name', 'hierarchy_snapshots'),
            );
        });
    });
});
