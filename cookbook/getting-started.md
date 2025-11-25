# Getting Started

Lineage provides closure table hierarchies for Eloquent models with O(1) ancestor/descendant queries. This guide will help you get started quickly.

## Installation

```bash
composer require cline/lineage
```

## Publish Configuration

```bash
php artisan vendor:publish --tag=lineage-config
```

## Run Migrations

```bash
php artisan migrate
```

## Basic Setup

Add the `HasLineage` trait to any model that needs hierarchical relationships:

```php
<?php

namespace App\Models;

use Cline\Lineage\Concerns\HasLineage;
use Illuminate\Database\Eloquent\Model;

class Seller extends Model
{
    use HasLineage;
}
```

## Quick Example

```php
use App\Models\Seller;
use Cline\Lineage\Facades\Lineage;

// Create a hierarchy
$ceo = Seller::create(['name' => 'CEO']);
$vp = Seller::create(['name' => 'VP of Sales']);
$manager = Seller::create(['name' => 'Regional Manager']);
$seller = Seller::create(['name' => 'Sales Rep']);

// Build the hierarchy
Lineage::addToHierarchy($ceo, 'seller');
Lineage::addToHierarchy($vp, 'seller', $ceo);
Lineage::addToHierarchy($manager, 'seller', $vp);
Lineage::addToHierarchy($seller, 'seller', $manager);

// Query the hierarchy
$ancestors = Lineage::getAncestors($seller, 'seller');
// Returns: [Regional Manager, VP of Sales, CEO]

$descendants = Lineage::getDescendants($ceo, 'seller');
// Returns: [VP of Sales, Regional Manager, Sales Rep]

$depth = Lineage::getDepth($seller, 'seller');
// Returns: 3
```

## Using the Fluent API

```php
// Using the for() conductor
Lineage::for($seller)
    ->type('seller')
    ->ancestors();

// Using the ofType() conductor
Lineage::ofType('seller')
    ->roots();
```

## Using the Trait Methods

```php
// Using trait methods directly on the model
$seller->addToLineage('seller', $manager);
$seller->getLineageAncestors('seller');
$seller->isLineageDescendantOf($ceo, 'seller');
```

## Next Steps

- [Basic Usage](basic-usage.md) - Learn the core operations
- [Fluent API](fluent-api.md) - Master the chainable interface
- [Configuration](configuration.md) - Customize Lineage for your needs
- [Multiple Hierarchy Types](multiple-types.md) - Manage different hierarchies
