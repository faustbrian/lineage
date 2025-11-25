<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Lineage\Facades;

use Cline\Lineage\Conductors\ForModelConductor;
use Cline\Lineage\Conductors\TypeConductor;
use Cline\Lineage\Contracts\HierarchyType;
use Cline\Lineage\LineageManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for the Lineage hierarchy management system.
 *
 * @method static void                                             addToHierarchy(Model $model, HierarchyType|string $type, ?Model $parent = null)
 * @method static void                                             attachToParent(Model $model, Model $parent, HierarchyType|string $type)
 * @method static array{model: Model, children: array<int, mixed>} buildTree(Model $model, HierarchyType|string $type)
 * @method static void                                             detachFromParent(Model $model, HierarchyType|string $type)
 * @method static ForModelConductor                                for(Model $model)
 * @method static Collection<int, Model>                           getAncestors(Model $model, HierarchyType|string $type, bool $includeSelf = false, ?int $maxDepth = null)
 * @method static int                                              getDepth(Model $model, HierarchyType|string $type)
 * @method static Collection<int, Model>                           getDescendants(Model $model, HierarchyType|string $type, bool $includeSelf = false, ?int $maxDepth = null)
 * @method static Collection<int, Model>                           getDirectChildren(Model $model, HierarchyType|string $type)
 * @method static ?Model                                           getDirectParent(Model $model, HierarchyType|string $type)
 * @method static Collection<int, Model>                           getPath(Model $model, HierarchyType|string $type)
 * @method static Collection<int, Model>                           getRootNodes(HierarchyType|string $type)
 * @method static Collection<int, Model>                           getRoots(Model $model, HierarchyType|string $type)
 * @method static Collection<int, Model>                           getSiblings(Model $model, HierarchyType|string $type, bool $includeSelf = false)
 * @method static bool                                             isAncestorOf(Model $potentialAncestor, Model $potentialDescendant, HierarchyType|string $type)
 * @method static bool                                             isDescendantOf(Model $potentialDescendant, Model $potentialAncestor, HierarchyType|string $type)
 * @method static bool                                             isInHierarchy(Model $model, HierarchyType|string $type)
 * @method static bool                                             isLeaf(Model $model, HierarchyType|string $type)
 * @method static bool                                             isRoot(Model $model, HierarchyType|string $type)
 * @method static void                                             moveToParent(Model $model, ?Model $newParent, HierarchyType|string $type)
 * @method static TypeConductor                                    ofType(HierarchyType|string $type)
 * @method static void                                             removeFromHierarchy(Model $model, HierarchyType|string $type)
 *
 * @see LineageManager
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Lineage extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return LineageManager::class;
    }
}
