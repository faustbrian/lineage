# Multiple Hierarchy Types

Lineage supports multiple hierarchy types, allowing a single model to participate in different hierarchical relationships simultaneously.

## Why Multiple Types?

Consider a company where:
- Sellers have a sales hierarchy (CEO → VP → Manager → Seller)
- Sellers can also be resellers with their own hierarchy
- Organizations have a separate corporate structure

One `User` or `Seller` model might exist in all three hierarchies with different positions in each.

## Using Multiple Types

### Adding to Different Hierarchies

```php
use Cline\Lineage\Facades\Lineage;

$user = User::find(1);

// Add to seller hierarchy
Lineage::addToHierarchy($user, 'seller', $salesManager);

// Add to reseller hierarchy
Lineage::addToHierarchy($user, 'reseller', $resellerManager);

// Add to organization hierarchy
Lineage::addToHierarchy($user, 'organization', $department);
```

### Querying Different Hierarchies

```php
// Get ancestors in seller hierarchy
$salesAncestors = Lineage::getAncestors($user, 'seller');

// Get ancestors in reseller hierarchy
$resellerAncestors = Lineage::getAncestors($user, 'reseller');

// Check position in each hierarchy
$sellerDepth = Lineage::getDepth($user, 'seller');
$resellerDepth = Lineage::getDepth($user, 'reseller');
```

### Hierarchies Are Isolated

Each hierarchy type is completely independent:

```php
// Different parents in each hierarchy
$sellerParent = Lineage::getDirectParent($user, 'seller');
$resellerParent = Lineage::getDirectParent($user, 'reseller');

// These are typically different models
$sellerParent !== $resellerParent; // true (usually)
```

## Type-Safe Hierarchies with Enums

For type safety, use a backed string enum:

```php
<?php

namespace App\Enums;

use Cline\Lineage\Contracts\HierarchyType;

enum HierarchyType: string implements HierarchyType
{
    case Seller = 'seller';
    case Reseller = 'reseller';
    case Organization = 'organization';

    public function value(): string
    {
        return $this->value;
    }
}
```

Then use the enum in your code:

```php
use App\Enums\HierarchyType;

// IDE autocompletion and type safety!
Lineage::addToHierarchy($user, HierarchyType::Seller, $manager);
Lineage::getAncestors($user, HierarchyType::Seller);
Lineage::isDescendantOf($user, $ceo, HierarchyType::Seller);
```

Configure the enum in `config/lineage.php`:

```php
'type_enum' => \App\Enums\HierarchyType::class,
```

## Using the Fluent API with Types

### For Model Conductor

```php
// Switch between types easily
$sellerConductor = Lineage::for($user)->type(HierarchyType::Seller);
$resellerConductor = Lineage::for($user)->type(HierarchyType::Reseller);

$sellerAncestors = $sellerConductor->ancestors();
$resellerAncestors = $resellerConductor->ancestors();
```

### Type Conductor

```php
// Work with all nodes in a specific hierarchy
$sellerRoots = Lineage::ofType(HierarchyType::Seller)->roots();
$resellerRoots = Lineage::ofType(HierarchyType::Reseller)->roots();
```

## Common Patterns

### Different Hierarchies, Same Model

```php
class User extends Model
{
    use HasLineage;

    public function getSellerAncestors(): Collection
    {
        return $this->getLineageAncestors(HierarchyType::Seller);
    }

    public function getResellerAncestors(): Collection
    {
        return $this->getLineageAncestors(HierarchyType::Reseller);
    }

    public function getOrganizationPath(): Collection
    {
        return $this->getLineagePath(HierarchyType::Organization);
    }
}
```

### Checking Multiple Hierarchies

```php
// Is user in ANY hierarchy?
$inAnyHierarchy = Lineage::isInHierarchy($user, HierarchyType::Seller)
    || Lineage::isInHierarchy($user, HierarchyType::Reseller)
    || Lineage::isInHierarchy($user, HierarchyType::Organization);

// Get all hierarchies user is in
$hierarchies = collect([HierarchyType::Seller, HierarchyType::Reseller, HierarchyType::Organization])
    ->filter(fn ($type) => Lineage::isInHierarchy($user, $type));
```

### Moving Between Hierarchies

Moving within one hierarchy doesn't affect others:

```php
// Move in seller hierarchy
Lineage::moveToParent($user, $newManager, HierarchyType::Seller);

// Reseller hierarchy is unchanged
$resellerParent = Lineage::getDirectParent($user, HierarchyType::Reseller);
// Still the same as before
```

### Removing from Specific Hierarchy

```php
// Remove from seller hierarchy only
Lineage::removeFromHierarchy($user, HierarchyType::Seller);

// User is still in other hierarchies
Lineage::isInHierarchy($user, HierarchyType::Reseller); // true
Lineage::isInHierarchy($user, HierarchyType::Seller);   // false
```

## Database Storage

All hierarchy types are stored in the same table, differentiated by the `type` column:

```sql
SELECT * FROM hierarchies WHERE type = 'seller';
SELECT * FROM hierarchies WHERE type = 'reseller';
```

This allows efficient querying within a type while maintaining isolation between types.
