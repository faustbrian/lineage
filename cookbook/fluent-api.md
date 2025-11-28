# Fluent API

Lineage provides a fluent, chainable API for managing hierarchies. This guide covers both conductor types.

## For Model Conductor

The `for()` conductor starts operations on a specific model:

```php
use Cline\Lineage\Facades\Lineage;

Lineage::for($model)->type('seller')->...
```

### Setting the Type

Always set the hierarchy type before performing operations:

```php
$conductor = Lineage::for($seller)->type('seller');
```

### Adding to Hierarchy

```php
// Add as root
Lineage::for($seller)->type('seller')->add();

// Add with parent
Lineage::for($seller)->type('seller')->add($manager);
```

### Attaching and Detaching

```php
// Attach to parent
Lineage::for($seller)->type('seller')->attachTo($manager);

// Detach from parent (become root)
Lineage::for($seller)->type('seller')->detach();
```

### Moving

```php
// Move to new parent
Lineage::for($seller)->type('seller')->moveTo($newManager);

// Move to become root
Lineage::for($seller)->type('seller')->moveTo(null);
```

### Removing

```php
Lineage::for($seller)->type('seller')->remove();
```

### Querying Relationships

```php
// Get ancestors
$ancestors = Lineage::for($seller)->type('seller')->ancestors();
$ancestorsWithSelf = Lineage::for($seller)->type('seller')->ancestors(includeSelf: true);
$nearestTwo = Lineage::for($seller)->type('seller')->ancestors(maxDepth: 2);

// Get descendants
$descendants = Lineage::for($ceo)->type('seller')->descendants();
$children = Lineage::for($ceo)->type('seller')->descendants(maxDepth: 1);

// Get parent
$parent = Lineage::for($seller)->type('seller')->parent();

// Get children
$children = Lineage::for($manager)->type('seller')->children();

// Get siblings
$siblings = Lineage::for($seller)->type('seller')->siblings();
$siblingsWithSelf = Lineage::for($seller)->type('seller')->siblings(includeSelf: true);
```

### Checking Relationships

```php
// Check ancestry
$isAncestor = Lineage::for($ceo)->type('seller')->isAncestorOf($seller);
$isDescendant = Lineage::for($seller)->type('seller')->isDescendantOf($ceo);

// Check position
$isInHierarchy = Lineage::for($seller)->type('seller')->isInHierarchy();
$isRoot = Lineage::for($ceo)->type('seller')->isRoot();
$isLeaf = Lineage::for($seller)->type('seller')->isLeaf();
```

### Getting Position Information

```php
// Get depth
$depth = Lineage::for($seller)->type('seller')->depth();

// Get roots
$roots = Lineage::for($seller)->type('seller')->roots();

// Get path from root
$path = Lineage::for($seller)->type('seller')->path();

// Build tree
$tree = Lineage::for($ceo)->type('seller')->tree();
```

### Chaining Operations

The conductor returns itself for modification operations, allowing chaining:

```php
Lineage::for($seller)
    ->type('seller')
    ->add($manager)
    ->detach()
    ->attachTo($newManager);
```

## Type Conductor

The `ofType()` conductor starts operations on a hierarchy type:

```php
use Cline\Lineage\Facades\Lineage;

Lineage::ofType('seller')->...
```

### Getting Root Nodes

```php
$roots = Lineage::ofType('seller')->roots();
```

### Adding Models

```php
// Add as root
Lineage::ofType('seller')->add($seller);

// Add with parent
Lineage::ofType('seller')->add($seller, $manager);
```

### Getting Model Conductor

You can transition to a model conductor with the type already set:

```php
$conductor = Lineage::ofType('seller')->for($seller);

// Now perform operations without setting type again
$ancestors = $conductor->ancestors();
$conductor->moveTo($newManager);
```

## Combining Approaches

You can use both conductors together for expressive code:

```php
// Get all roots in the seller hierarchy
$roots = Lineage::ofType('seller')->roots();

// For each root, get all descendants
foreach ($roots as $root) {
    $descendants = Lineage::for($root)
        ->type('seller')
        ->descendants();

    // Or using ofType
    $descendants = Lineage::ofType('seller')
        ->for($root)
        ->descendants();
}
```

## Error Handling

The conductor throws a `RuntimeException` if you try to perform operations without setting the type:

```php
// This will throw an exception
Lineage::for($seller)->ancestors(); // RuntimeException: Hierarchy type must be set
```

Always set the type first:

```php
Lineage::for($seller)->type('seller')->ancestors(); // Works!
```
