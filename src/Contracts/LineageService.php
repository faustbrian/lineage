<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Lineage\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Contract for the Lineage service.
 *
 * Defines the core operations for managing hierarchical relationships
 * using the closure table pattern.
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface LineageService
{
    /**
     * Add a model to a hierarchy under an optional parent.
     * Creates the self-referencing row and all ancestor relationships.
     *
     * @param Model                $model  The model to add to the hierarchy
     * @param HierarchyType|string $type   The hierarchy type
     * @param null|Model           $parent Optional parent model
     */
    public function addToHierarchy(
        Model $model,
        HierarchyType|string $type,
        ?Model $parent = null,
    ): void;

    /**
     * Attach a model to a parent in an existing hierarchy.
     *
     * @param Model                $model  The model to attach
     * @param Model                $parent The parent model to attach to
     * @param HierarchyType|string $type   The hierarchy type
     */
    public function attachToParent(
        Model $model,
        Model $parent,
        HierarchyType|string $type,
    ): void;

    /**
     * Detach a model from its parent (keeps in hierarchy as root).
     *
     * @param Model                $model The model to detach
     * @param HierarchyType|string $type  The hierarchy type
     */
    public function detachFromParent(
        Model $model,
        HierarchyType|string $type,
    ): void;

    /**
     * Remove a model completely from a hierarchy.
     *
     * @param Model                $model The model to remove
     * @param HierarchyType|string $type  The hierarchy type
     */
    public function removeFromHierarchy(
        Model $model,
        HierarchyType|string $type,
    ): void;

    /**
     * Move a model to a new parent.
     *
     * @param Model                $model     The model to move
     * @param null|Model           $newParent The new parent (null to make root)
     * @param HierarchyType|string $type      The hierarchy type
     */
    public function moveToParent(
        Model $model,
        ?Model $newParent,
        HierarchyType|string $type,
    ): void;

    /**
     * Get all ancestors of a model.
     *
     * @param  Model                  $model       The model to get ancestors for
     * @param  HierarchyType|string   $type        The hierarchy type
     * @param  bool                   $includeSelf Whether to include the model itself
     * @param  null|int               $maxDepth    Maximum depth to traverse
     * @return Collection<int, Model>
     */
    public function getAncestors(
        Model $model,
        HierarchyType|string $type,
        bool $includeSelf = false,
        ?int $maxDepth = null,
    ): Collection;

    /**
     * Get all descendants of a model.
     *
     * @param  Model                  $model       The model to get descendants for
     * @param  HierarchyType|string   $type        The hierarchy type
     * @param  bool                   $includeSelf Whether to include the model itself
     * @param  null|int               $maxDepth    Maximum depth to traverse
     * @return Collection<int, Model>
     */
    public function getDescendants(
        Model $model,
        HierarchyType|string $type,
        bool $includeSelf = false,
        ?int $maxDepth = null,
    ): Collection;

    /**
     * Get the direct parent of a model.
     *
     * @param Model                $model The model to get parent for
     * @param HierarchyType|string $type  The hierarchy type
     */
    public function getDirectParent(
        Model $model,
        HierarchyType|string $type,
    ): ?Model;

    /**
     * Get the direct children of a model.
     *
     * @param  Model                  $model The model to get children for
     * @param  HierarchyType|string   $type  The hierarchy type
     * @return Collection<int, Model>
     */
    public function getDirectChildren(
        Model $model,
        HierarchyType|string $type,
    ): Collection;

    /**
     * Check if a model is an ancestor of another.
     *
     * @param Model                $potentialAncestor   The potential ancestor
     * @param Model                $potentialDescendant The potential descendant
     * @param HierarchyType|string $type                The hierarchy type
     */
    public function isAncestorOf(
        Model $potentialAncestor,
        Model $potentialDescendant,
        HierarchyType|string $type,
    ): bool;

    /**
     * Check if a model is a descendant of another.
     *
     * @param Model                $potentialDescendant The potential descendant
     * @param Model                $potentialAncestor   The potential ancestor
     * @param HierarchyType|string $type                The hierarchy type
     */
    public function isDescendantOf(
        Model $potentialDescendant,
        Model $potentialAncestor,
        HierarchyType|string $type,
    ): bool;

    /**
     * Get the depth of a model in the hierarchy.
     *
     * @param Model                $model The model to get depth for
     * @param HierarchyType|string $type  The hierarchy type
     */
    public function getDepth(
        Model $model,
        HierarchyType|string $type,
    ): int;

    /**
     * Get the root ancestor(s) of a model.
     *
     * @param  Model                  $model The model to get roots for
     * @param  HierarchyType|string   $type  The hierarchy type
     * @return Collection<int, Model>
     */
    public function getRoots(
        Model $model,
        HierarchyType|string $type,
    ): Collection;

    /**
     * Build a tree structure from a model's descendants.
     *
     * @param  Model                                            $model The root model
     * @param  HierarchyType|string                             $type  The hierarchy type
     * @return array{model: Model, children: array<int, mixed>}
     */
    public function buildTree(
        Model $model,
        HierarchyType|string $type,
    ): array;

    /**
     * Get the full path from root to model.
     *
     * @param  Model                  $model The model to get path for
     * @param  HierarchyType|string   $type  The hierarchy type
     * @return Collection<int, Model>
     */
    public function getPath(
        Model $model,
        HierarchyType|string $type,
    ): Collection;

    /**
     * Check if a model is in a hierarchy.
     *
     * @param Model                $model The model to check
     * @param HierarchyType|string $type  The hierarchy type
     */
    public function isInHierarchy(
        Model $model,
        HierarchyType|string $type,
    ): bool;

    /**
     * Check if a model is a root in a hierarchy.
     *
     * @param Model                $model The model to check
     * @param HierarchyType|string $type  The hierarchy type
     */
    public function isRoot(
        Model $model,
        HierarchyType|string $type,
    ): bool;

    /**
     * Check if a model is a leaf (has no children) in a hierarchy.
     *
     * @param Model                $model The model to check
     * @param HierarchyType|string $type  The hierarchy type
     */
    public function isLeaf(
        Model $model,
        HierarchyType|string $type,
    ): bool;

    /**
     * Get siblings (models with the same parent) in a hierarchy.
     *
     * @param  Model                  $model       The model to get siblings for
     * @param  HierarchyType|string   $type        The hierarchy type
     * @param  bool                   $includeSelf Whether to include the model itself
     * @return Collection<int, Model>
     */
    public function getSiblings(
        Model $model,
        HierarchyType|string $type,
        bool $includeSelf = false,
    ): Collection;

    /**
     * Get all root nodes for a hierarchy type.
     *
     * @param  HierarchyType|string   $type The hierarchy type
     * @return Collection<int, Model>
     */
    public function getRootNodes(HierarchyType|string $type): Collection;
}
