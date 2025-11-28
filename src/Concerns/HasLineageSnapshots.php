<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Lineage\Concerns;

use Cline\Lineage\Contracts\HierarchyType;
use Cline\Lineage\Database\HierarchySnapshot;
use Cline\Lineage\Events\SnapshotCleared;
use Cline\Lineage\Events\SnapshotCreated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;

use function count;

/**
 * Trait for models that can have hierarchy snapshots attached.
 *
 * Snapshots capture the full hierarchy chain at a specific point in time,
 * preserving historical relationships even when hierarchies change.
 *
 * @mixin Model
 *
 * @author Brian Faust <brian@cline.sh>
 */
trait HasLineageSnapshots
{
    /**
     * Get all hierarchy snapshots for this model.
     *
     * @return MorphMany<HierarchySnapshot, $this>
     */
    public function lineageSnapshots(): MorphMany
    {
        /** @var class-string<HierarchySnapshot> $snapshotModel */
        $snapshotModel = Config::get('lineage.snapshots.model', HierarchySnapshot::class);

        return $this->morphMany($snapshotModel, 'context');
    }

    /**
     * Snapshot the current hierarchy chain for a given node and type.
     *
     * This captures the full ancestor chain at this point in time.
     * If snapshots already exist for this type, they are replaced.
     *
     * @param Model                $node The node whose ancestors to snapshot (must use HasLineage trait)
     * @param HierarchyType|string $type The hierarchy type
     */
    public function snapshotLineage(Model $node, HierarchyType|string $type): void
    {
        $typeValue = $type instanceof HierarchyType ? $type->value() : $type;

        // Check if snapshots are enabled
        if (!Config::get('lineage.snapshots.enabled', true)) {
            return;
        }

        // Delete existing snapshots for this context+type
        $this->lineageSnapshots()
            ->where('type', $typeValue)
            ->delete();

        // Get all ancestors including self (the node at depth 0)
        // Already ordered by depth: 0=self, 1=parent, 2=grandparent, etc.
        /** @var Collection<int, Model> $ancestors */
        $ancestors = $node->getLineageAncestors($type, includeSelf: true); // @phpstan-ignore method.notFound (method exists via HasLineage trait)

        // Create snapshots for each ancestor at their depth
        /** @var array<int, HierarchySnapshot> $createdSnapshots */
        $createdSnapshots = [];

        /** @var Model $ancestor */
        foreach ($ancestors->values() as $depth => $ancestor) {
            $snapshot = $this->lineageSnapshots()->create([
                'type' => $typeValue,
                'depth' => $depth,
                'ancestor_id' => $ancestor->getKey(),
            ]);
            $createdSnapshots[] = $snapshot;
        }

        // Dispatch event if events are enabled
        if (Config::get('lineage.events.enabled', true)) {
            Event::dispatch(
                new SnapshotCreated(
                    context: $this,
                    type: $typeValue,
                    count: count($createdSnapshots),
                    snapshots: $createdSnapshots,
                ),
            );
        }
    }

    /**
     * Get snapshots for a specific hierarchy type.
     *
     * @return Collection<int, HierarchySnapshot>
     */
    public function getLineageSnapshots(HierarchyType|string $type): Collection
    {
        $typeValue = $type instanceof HierarchyType ? $type->value() : $type;

        /** @var Collection<int, HierarchySnapshot> */
        return $this->lineageSnapshots()
            ->where('type', $typeValue)
            ->orderBy('depth')
            ->get();
    }

    /**
     * Check if snapshots exist for a specific hierarchy type.
     */
    public function hasLineageSnapshots(HierarchyType|string $type): bool
    {
        $typeValue = $type instanceof HierarchyType ? $type->value() : $type;

        return $this->lineageSnapshots()
            ->where('type', $typeValue)
            ->exists();
    }

    /**
     * Clear all snapshots for a specific hierarchy type.
     */
    public function clearLineageSnapshots(HierarchyType|string $type): void
    {
        $typeValue = $type instanceof HierarchyType ? $type->value() : $type;

        $count = $this->lineageSnapshots()
            ->where('type', $typeValue)
            ->count();

        $this->lineageSnapshots()
            ->where('type', $typeValue)
            ->delete();

        // Dispatch event if events are enabled and snapshots were cleared
        if ($count > 0 && Config::get('lineage.events.enabled', true)) {
            Event::dispatch(
                new SnapshotCleared(
                    context: $this,
                    type: $typeValue,
                    count: $count,
                ),
            );
        }
    }

    /**
     * Get the snapshot at a specific depth.
     */
    public function getLineageSnapshotAtDepth(HierarchyType|string $type, int $depth): ?HierarchySnapshot
    {
        $typeValue = $type instanceof HierarchyType ? $type->value() : $type;

        return $this->lineageSnapshots()
            ->where('type', $typeValue)
            ->where('depth', $depth)
            ->first();
    }

    /**
     * Get the direct node from snapshot (depth 0).
     */
    public function getDirectLineageSnapshot(HierarchyType|string $type): ?HierarchySnapshot
    {
        return $this->getLineageSnapshotAtDepth($type, 0);
    }

    /**
     * Get all ancestor IDs from snapshots as an array.
     *
     * Useful for queries and exports.
     *
     * @return array<int, mixed>
     */
    public function getLineageSnapshotAncestorIds(HierarchyType|string $type): array
    {
        return $this->getLineageSnapshots($type)
            ->pluck('ancestor_id')
            ->toArray();
    }

    /**
     * Get snapshots as an array suitable for export/serialization.
     *
     * @return array<int, array{ancestor_id: mixed, depth: int, type: string}>
     */
    public function getLineageSnapshotsArray(HierarchyType|string $type): array
    {
        /** @var array<int, array{ancestor_id: mixed, depth: int, type: string}> */
        return $this->getLineageSnapshots($type)
            ->map(fn (HierarchySnapshot $snapshot): array => [
                'ancestor_id' => $snapshot->ancestor_id,
                'depth' => $snapshot->depth,
                'type' => $snapshot->type,
            ])
            ->toArray();
    }
}
