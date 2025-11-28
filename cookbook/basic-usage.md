# Basic Usage

This guide covers the fundamental operations for managing hierarchies with Lineage.

## Adding to a Hierarchy

### As a Root Node

```php
use Cline\Lineage\Facades\Lineage;

$ceo = Seller::create(['name' => 'CEO']);
Lineage::addToHierarchy($ceo, 'seller');
```

### With a Parent

```php
$vp = Seller::create(['name' => 'VP']);
Lineage::addToHierarchy($vp, 'seller', $ceo);
```

## Querying Relationships

### Get Ancestors

```php
// Get all ancestors (ordered from nearest to farthest)
$ancestors = Lineage::getAncestors($seller, 'seller');

// Include self in results
$ancestorsWithSelf = Lineage::getAncestors($seller, 'seller', includeSelf: true);

// Limit depth
$nearestTwo = Lineage::getAncestors($seller, 'seller', maxDepth: 2);
```

### Get Descendants

```php
// Get all descendants (ordered from nearest to farthest)
$descendants = Lineage::getDescendants($ceo, 'seller');

// Include self in results
$descendantsWithSelf = Lineage::getDescendants($ceo, 'seller', includeSelf: true);

// Limit depth (direct children only)
$directReports = Lineage::getDescendants($ceo, 'seller', maxDepth: 1);
```

### Get Direct Relationships

```php
// Get direct parent
$parent = Lineage::getDirectParent($seller, 'seller');

// Get direct children
$children = Lineage::getDirectChildren($manager, 'seller');
```

## Checking Relationships

```php
// Check if one model is ancestor of another
$isAncestor = Lineage::isAncestorOf($ceo, $seller, 'seller'); // true

// Check if one model is descendant of another
$isDescendant = Lineage::isDescendantOf($seller, $ceo, 'seller'); // true

// Check if model is in a hierarchy
$isInHierarchy = Lineage::isInHierarchy($seller, 'seller'); // true

// Check if model is a root (no parent)
$isRoot = Lineage::isRoot($ceo, 'seller'); // true

// Check if model is a leaf (no children)
$isLeaf = Lineage::isLeaf($seller, 'seller'); // true
```

## Getting Depth and Position

```php
// Get depth in hierarchy (0 = root)
$depth = Lineage::getDepth($seller, 'seller');

// Get root(s) of the hierarchy
$roots = Lineage::getRoots($seller, 'seller');

// Get siblings (same parent)
$siblings = Lineage::getSiblings($seller, 'seller');

// Get path from root to model
$path = Lineage::getPath($seller, 'seller');
// Returns: [CEO, VP, Manager, Seller]
```

## Building Trees

```php
// Build a tree structure starting from a node
$tree = Lineage::buildTree($ceo, 'seller');

// Returns:
// [
//     'model' => $ceo,
//     'children' => [
//         [
//             'model' => $vp,
//             'children' => [
//                 [
//                     'model' => $manager,
//                     'children' => [
//                         [
//                             'model' => $seller,
//                             'children' => []
//                         ]
//                     ]
//                 ]
//             ]
//         ]
//     ]
// ]
```

## Getting All Roots

```php
// Get all root nodes for a hierarchy type
$allRoots = Lineage::getRootNodes('seller');
```

## Modifying Hierarchies

### Detach from Parent

```php
// Detach from parent (become a root)
Lineage::detachFromParent($manager, 'seller');
```

### Attach to New Parent

```php
// Attach an existing node to a parent
Lineage::attachToParent($manager, $newVp, 'seller');
```

### Move to Different Parent

```php
// Move node (with all descendants) to new parent
Lineage::moveToParent($manager, $newVp, 'seller');

// Move to become root
Lineage::moveToParent($manager, null, 'seller');
```

### Remove from Hierarchy

```php
// Remove completely from hierarchy
Lineage::removeFromHierarchy($seller, 'seller');
```

## Using the Trait

All operations are also available directly on models using the `HasLineage` trait:

```php
$seller->addToLineage('seller', $manager);
$seller->getLineageAncestors('seller');
$seller->getLineageDescendants('seller');
$seller->getLineageParent('seller');
$seller->getLineageChildren('seller');
$seller->isLineageAncestorOf($other, 'seller');
$seller->isLineageDescendantOf($other, 'seller');
$seller->getLineageDepth('seller');
$seller->getLineageRoots('seller');
$seller->getLineagePath('seller');
$seller->buildLineageTree('seller');
$seller->isInLineage('seller');
$seller->isLineageRoot('seller');
$seller->isLineageLeaf('seller');
$seller->getLineageSiblings('seller');
$seller->detachFromLineageParent('seller');
$seller->attachToLineageParent($parent, 'seller');
$seller->moveToLineageParent($newParent, 'seller');
$seller->removeFromLineage('seller');
```
