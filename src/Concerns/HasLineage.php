<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Lineage\Concerns;

use Cline\Lineage\Contracts\HierarchyType;
use Cline\Lineage\Contracts\LineageService;
use Cline\Lineage\Database\Hierarchy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

use function app;

/**
 * Trait for models that participate in hierarchies.
 *
 * Provides convenient methods for managing hierarchical relationships
 * using the closure table pattern. Use this trait on any Eloquent model
 * that needs to participate in hierarchies.
 *
 * @mixin Model
 *
 * @author Brian Faust <brian@cline.sh>
 */
trait HasLineage
{
    /**
     * Get all hierarchy entries where this model is an ancestor.
     *
     * @return MorphMany<Hierarchy, $this>
     */
    public function lineageAsAncestor(): MorphMany
    {
        /** @var class-string<Hierarchy> $hierarchyModel */
        $hierarchyModel = Config::get('lineage.models.hierarchy', Hierarchy::class);

        return $this->morphMany($hierarchyModel, 'ancestor');
    }

    /**
     * Get all hierarchy entries where this model is a descendant.
     *
     * @return MorphMany<Hierarchy, $this>
     */
    public function lineageAsDescendant(): MorphMany
    {
        /** @var class-string<Hierarchy> $hierarchyModel */
        $hierarchyModel = Config::get('lineage.models.hierarchy', Hierarchy::class);

        return $this->morphMany($hierarchyModel, 'descendant');
    }

    /**
     * Add this model to a hierarchy.
     *
     * @param HierarchyType|string $type   The hierarchy type
     * @param null|Model           $parent Optional parent model
     */
    public function addToLineage(HierarchyType|string $type, ?Model $parent = null): void
    {
        $this->getLineageService()->addToHierarchy($this, $type, $parent);
    }

    /**
     * Attach this model to a parent in an existing hierarchy.
     *
     * @param Model                $parent The parent model
     * @param HierarchyType|string $type   The hierarchy type
     */
    public function attachToLineageParent(Model $parent, HierarchyType|string $type): void
    {
        $this->getLineageService()->attachToParent($this, $parent, $type);
    }

    /**
     * Detach this model from its parent (become a root).
     *
     * @param HierarchyType|string $type The hierarchy type
     */
    public function detachFromLineageParent(HierarchyType|string $type): void
    {
        $this->getLineageService()->detachFromParent($this, $type);
    }

    /**
     * Remove this model from a hierarchy completely.
     *
     * @param HierarchyType|string $type The hierarchy type
     */
    public function removeFromLineage(HierarchyType|string $type): void
    {
        $this->getLineageService()->removeFromHierarchy($this, $type);
    }

    /**
     * Move this model to a new parent.
     *
     * @param null|Model           $newParent The new parent (null to make root)
     * @param HierarchyType|string $type      The hierarchy type
     */
    public function moveToLineageParent(?Model $newParent, HierarchyType|string $type): void
    {
        $this->getLineageService()->moveToParent($this, $newParent, $type);
    }

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
    ): Collection {
        return $this->getLineageService()->getAncestors($this, $type, $includeSelf, $maxDepth);
    }

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
    ): Collection {
        return $this->getLineageService()->getDescendants($this, $type, $includeSelf, $maxDepth);
    }

    /**
     * Get the direct parent of this model.
     *
     * @param HierarchyType|string $type The hierarchy type
     */
    public function getLineageParent(HierarchyType|string $type): ?Model
    {
        return $this->getLineageService()->getDirectParent($this, $type);
    }

    /**
     * Get the direct children of this model.
     *
     * @param  HierarchyType|string   $type The hierarchy type
     * @return Collection<int, Model>
     */
    public function getLineageChildren(HierarchyType|string $type): Collection
    {
        return $this->getLineageService()->getDirectChildren($this, $type);
    }

    /**
     * Check if this model is an ancestor of another.
     *
     * @param Model                $model The potential descendant
     * @param HierarchyType|string $type  The hierarchy type
     */
    public function isLineageAncestorOf(Model $model, HierarchyType|string $type): bool
    {
        return $this->getLineageService()->isAncestorOf($this, $model, $type);
    }

    /**
     * Check if this model is a descendant of another.
     *
     * @param Model                $model The potential ancestor
     * @param HierarchyType|string $type  The hierarchy type
     */
    public function isLineageDescendantOf(Model $model, HierarchyType|string $type): bool
    {
        return $this->getLineageService()->isDescendantOf($this, $model, $type);
    }

    /**
     * Get this model's depth in the hierarchy.
     *
     * @param HierarchyType|string $type The hierarchy type
     */
    public function getLineageDepth(HierarchyType|string $type): int
    {
        return $this->getLineageService()->getDepth($this, $type);
    }

    /**
     * Get the root(s) of this model's hierarchy.
     *
     * @param  HierarchyType|string   $type The hierarchy type
     * @return Collection<int, Model>
     */
    public function getLineageRoots(HierarchyType|string $type): Collection
    {
        return $this->getLineageService()->getRoots($this, $type);
    }

    /**
     * Build a tree from this model's descendants.
     *
     * @param  HierarchyType|string                             $type The hierarchy type
     * @return array{model: Model, children: array<int, mixed>}
     */
    public function buildLineageTree(HierarchyType|string $type): array
    {
        return $this->getLineageService()->buildTree($this, $type);
    }

    /**
     * Get the path from root to this model.
     *
     * @param  HierarchyType|string   $type The hierarchy type
     * @return Collection<int, Model>
     */
    public function getLineagePath(HierarchyType|string $type): Collection
    {
        return $this->getLineageService()->getPath($this, $type);
    }

    /**
     * Check if this model is in a hierarchy.
     *
     * @param HierarchyType|string $type The hierarchy type
     */
    public function isInLineage(HierarchyType|string $type): bool
    {
        return $this->getLineageService()->isInHierarchy($this, $type);
    }

    /**
     * Check if this model is a root in a hierarchy.
     *
     * @param HierarchyType|string $type The hierarchy type
     */
    public function isLineageRoot(HierarchyType|string $type): bool
    {
        return $this->getLineageService()->isRoot($this, $type);
    }

    /**
     * Check if this model is a leaf (has no children) in a hierarchy.
     *
     * @param HierarchyType|string $type The hierarchy type
     */
    public function isLineageLeaf(HierarchyType|string $type): bool
    {
        return $this->getLineageService()->isLeaf($this, $type);
    }

    /**
     * Get siblings (models with the same parent) in a hierarchy.
     *
     * @param  HierarchyType|string   $type        The hierarchy type
     * @param  bool                   $includeSelf Whether to include this model
     * @return Collection<int, Model>
     */
    public function getLineageSiblings(HierarchyType|string $type, bool $includeSelf = false): Collection
    {
        return $this->getLineageService()->getSiblings($this, $type, $includeSelf);
    }

    /**
     * Get the lineage service from the container.
     */
    protected function getLineageService(): LineageService
    {
        return app(LineageService::class);
    }
}
