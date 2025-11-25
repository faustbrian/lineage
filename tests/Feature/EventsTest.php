<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Lineage\Events\NodeAttached;
use Cline\Lineage\Events\NodeDetached;
use Cline\Lineage\Events\NodeMoved;
use Cline\Lineage\Events\NodeRemoved;
use Cline\Lineage\Facades\Lineage;
use Illuminate\Support\Facades\Event;

describe('Hierarchy Events', function (): void {
    test('dispatches NodeAttached event when attaching to parent', function (): void {
        Event::fake([NodeAttached::class]);

        $parent = user();
        $child = user();

        Lineage::addToHierarchy($parent, 'seller');
        Lineage::addToHierarchy($child, 'seller', $parent);

        Event::assertDispatched(NodeAttached::class, fn (NodeAttached $event): bool => $event->node->id === $child->id
            && $event->parent->id === $parent->id
            && $event->type === 'seller');
    });

    test('dispatches NodeDetached event when detaching from parent', function (): void {
        Event::fake([NodeDetached::class]);

        [$parent, $child] = createHierarchyChain(2);

        Lineage::detachFromParent($child, 'seller');

        Event::assertDispatched(NodeDetached::class, fn (NodeDetached $event): bool => $event->node->id === $child->id
            && $event->previousParent->id === $parent->id
            && $event->type === 'seller');
    });

    test('dispatches NodeMoved event when moving to new parent', function (): void {
        Event::fake([NodeMoved::class]);

        $root1 = user();
        $root2 = user();
        $child = user();

        Lineage::addToHierarchy($root1, 'seller');
        Lineage::addToHierarchy($root2, 'seller');
        Lineage::addToHierarchy($child, 'seller', $root1);

        Event::fake([NodeMoved::class]); // Reset after setup

        Lineage::moveToParent($child, $root2, 'seller');

        Event::assertDispatched(NodeMoved::class, fn (NodeMoved $event): bool => $event->node->id === $child->id
            && $event->previousParent->id === $root1->id
            && $event->newParent->id === $root2->id
            && $event->type === 'seller');
    });

    test('dispatches NodeRemoved event when removing from hierarchy', function (): void {
        Event::fake([NodeRemoved::class]);

        $user = user();
        Lineage::addToHierarchy($user, 'seller');

        Event::fake([NodeRemoved::class]); // Reset after setup

        Lineage::removeFromHierarchy($user, 'seller');

        Event::assertDispatched(NodeRemoved::class, fn (NodeRemoved $event): bool => $event->node->id === $user->id
            && $event->type === 'seller');
    });

    test('events can be disabled via config', function (): void {
        config()->set('lineage.events.enabled', false);

        Event::fake([NodeAttached::class]);

        $parent = user();
        $child = user();

        Lineage::addToHierarchy($parent, 'seller');
        Lineage::addToHierarchy($child, 'seller', $parent);

        Event::assertNotDispatched(NodeAttached::class);
    });
});
