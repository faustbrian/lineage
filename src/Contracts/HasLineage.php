<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Lineage\Contracts;

use Cline\Lineage\Database\Hierarchy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * Contract for models that participate in hierarchies.
 *
 * Implement this interface on Eloquent models that need hierarchical
 * relationships. Use the HasLineage trait for the default implementation.
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface HasLineage
{
    /**
     * Get all hierarchy entries where this model is an ancestor.
     *
     * @return MorphMany<Hierarchy, Model>
     */
    public function lineageAsAncestor(): MorphMany;

    /**
     * Get all hierarchy entries where this model is a descendant.
     *
     * @return MorphMany<Hierarchy, Model>
     */
    public function lineageAsDescendant(): MorphMany;

    /**
     * Add this model to a hierarchy.
     *
     * @param HierarchyType|string $type   The hierarchy type
     * @param null|Model           $parent Optional parent model
     */
    public function addToLineage(HierarchyType|string $type, ?Model $parent = null): void;

    /**
     * Attach this model to a parent in an existing hierarchy.
     *
     * @param Model                $parent The parent model
     * @param HierarchyType|string $type   The hierarchy type
     */
    public function attachToLineageParent(Model $parent, HierarchyType|string $type): void;

    /**
     * Detach this model from its parent (become a root).
     *
     * @param HierarchyType|string $type The hierarchy type
     */
    public function detachFromLineageParent(HierarchyType|string $type): void;

    /**
     * Remove this model from a hierarchy completely.
     *
     * @param HierarchyType|string $type The hierarchy type
     */
    public function removeFromLineage(HierarchyType|string $type): void;

    /**
     * Move this model to a new parent.
     *
     * @param null|Model           $newParent The new parent (null to make root)
     * @param HierarchyType|string $type      The hierarchy type
     */
    public function moveToLineageParent(?Model $newParent, HierarchyType|string $type): void;

    /**
     * Get all ancestors of this model.
     *
     * @param  HierarchyType|string   $type        The hierarchy type
     * @param  bool                   $includeSelf Whether to include this model
     * @param  null|int               $maxDepth    Maximum depth to traverse
     * @return Collection<int, Model>
     */
    public function getLineageAncestors(
        HierarchyType|string $type,
        bool $includeSelf = false,
        ?int $maxDepth = null,
    ): Collection;

    /**
     * Get all descendants of this model.
     *
     * @param  HierarchyType|string   $type        The hierarchy type
     * @param  bool                   $includeSelf Whether to include this model
     * @param  null|int               $maxDepth    Maximum depth to traverse
     * @return Collection<int, Model>
     */
    public function getLineageDescendants(
        HierarchyType|string $type,
        bool $includeSelf = false,
        ?int $maxDepth = null,
    ): Collection;

    /**
     * Get the direct parent of this model.
     *
     * @param HierarchyType|string $type The hierarchy type
     */
    public function getLineageParent(HierarchyType|string $type): ?Model;

    /**
     * Get the direct children of this model.
     *
     * @param  HierarchyType|string   $type The hierarchy type
     * @return Collection<int, Model>
     */
    public function getLineageChildren(HierarchyType|string $type): Collection;

    /**
     * Check if this model is an ancestor of another.
     *
     * @param Model                $model The potential descendant
     * @param HierarchyType|string $type  The hierarchy type
     */
    public function isLineageAncestorOf(Model $model, HierarchyType|string $type): bool;

    /**
     * Check if this model is a descendant of another.
     *
     * @param Model                $model The potential ancestor
     * @param HierarchyType|string $type  The hierarchy type
     */
    public function isLineageDescendantOf(Model $model, HierarchyType|string $type): bool;

    /**
     * Get this model's depth in the hierarchy.
     *
     * @param HierarchyType|string $type The hierarchy type
     */
    public function getLineageDepth(HierarchyType|string $type): int;

    /**
     * Get the root(s) of this model's hierarchy.
     *
     * @param  HierarchyType|string   $type The hierarchy type
     * @return Collection<int, Model>
     */
    public function getLineageRoots(HierarchyType|string $type): Collection;

    /**
     * Build a tree from this model's descendants.
     *
     * @param  HierarchyType|string                             $type The hierarchy type
     * @return array{model: Model, children: array<int, mixed>}
     */
    public function buildLineageTree(HierarchyType|string $type): array;

    /**
     * Get the path from root to this model.
     *
     * @param  HierarchyType|string   $type The hierarchy type
     * @return Collection<int, Model>
     */
    public function getLineagePath(HierarchyType|string $type): Collection;
}
