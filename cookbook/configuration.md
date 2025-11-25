# Configuration

Lineage is highly configurable. This guide covers all available options.

## Publishing Configuration

```bash
php artisan vendor:publish --tag=lineage-config
```

This creates `config/lineage.php`.

## Primary Key Type

Control the primary key type for the hierarchies table:

```php
'primary_key_type' => env('LINEAGE_PRIMARY_KEY_TYPE', 'id'),
```

Supported values:
- `'id'` - Auto-incrementing integers (default)
- `'ulid'` - ULIDs for sortable, time-ordered identifiers
- `'uuid'` - UUIDs for globally unique identifiers

## Morph Types

Configure polymorphic relationship column types separately for ancestors and descendants:

```php
'ancestor_morph_type' => env('LINEAGE_ANCESTOR_MORPH_TYPE', 'morph'),
'descendant_morph_type' => env('LINEAGE_DESCENDANT_MORPH_TYPE', 'morph'),
```

Supported values:
- `'morph'` - Standard morphs (default)
- `'uuidMorph'` - UUID-based morphs
- `'ulidMorph'` - ULID-based morphs

## Maximum Depth

Limit hierarchy depth to prevent abuse:

```php
'max_depth' => env('LINEAGE_MAX_DEPTH', 10),
```

Set to `null` for unlimited depth (not recommended for production).

## Custom Hierarchy Model

Use a custom Hierarchy model:

```php
'models' => [
    'hierarchy' => \App\Models\CustomHierarchy::class,
],
```

Your custom model must extend `Cline\Lineage\Database\Hierarchy`.

## Table Name

Customize the table name:

```php
'table_name' => env('LINEAGE_TABLE', 'hierarchies'),
```

## Polymorphic Key Mapping

Map models to their primary key columns for mixed key types:

```php
'morphKeyMap' => [
    \App\Models\User::class => 'id',
    \App\Models\Seller::class => 'ulid',
    \App\Models\Organization::class => 'uuid',
],
```

### Enforced Key Mapping

Enable strict enforcement to throw exceptions for unmapped models:

```php
'enforceMorphKeyMap' => [
    \App\Models\User::class => 'id',
    \App\Models\Seller::class => 'ulid',
],
```

**Note:** Only configure either `morphKeyMap` or `enforceMorphKeyMap`, not both.

## Events

Control event dispatching:

```php
'events' => [
    'enabled' => env('LINEAGE_EVENTS_ENABLED', true),
],
```

Events dispatched:
- `NodeAttached` - When a node is attached to a parent
- `NodeDetached` - When a node is detached from its parent
- `NodeMoved` - When a node is moved to a new parent
- `NodeRemoved` - When a node is completely removed

## Caching

Configure hierarchy query caching:

```php
'cache' => [
    'enabled' => env('LINEAGE_CACHE_ENABLED', false),
    'store' => env('LINEAGE_CACHE_STORE'),
    'prefix' => env('LINEAGE_CACHE_PREFIX', 'lineage'),
    'ttl' => env('LINEAGE_CACHE_TTL', 3600),
],
```

## Strict Mode

Enable strict mode for development:

```php
'strict' => env('LINEAGE_STRICT', true),
```

Strict mode enforces:
- Detailed error messages for circular references
- Clear exceptions for depth violations
- Type mismatch detection

## Database Connection

Use a separate database connection:

```php
'connection' => env('LINEAGE_CONNECTION'),
```

## Hierarchy Types

Define available hierarchy types (optional):

```php
'types' => [
    'seller',
    'reseller',
    'organization',
],
```

Or use a backed enum:

```php
'type_enum' => \App\Enums\HierarchyType::class,
```

## Environment Variables

All configuration can be set via environment variables:

```env
LINEAGE_PRIMARY_KEY_TYPE=ulid
LINEAGE_ANCESTOR_MORPH_TYPE=ulidMorph
LINEAGE_DESCENDANT_MORPH_TYPE=ulidMorph
LINEAGE_MAX_DEPTH=15
LINEAGE_TABLE=custom_hierarchies
LINEAGE_EVENTS_ENABLED=true
LINEAGE_CACHE_ENABLED=true
LINEAGE_CACHE_STORE=redis
LINEAGE_CACHE_PREFIX=hierarchy
LINEAGE_CACHE_TTL=7200
LINEAGE_STRICT=true
LINEAGE_CONNECTION=hierarchy_db
```

## Example Configuration

Here's a complete example for a production setup:

```php
<?php

return [
    'primary_key_type' => 'ulid',
    'ancestor_morph_type' => 'ulidMorph',
    'descendant_morph_type' => 'ulidMorph',
    'max_depth' => 10,

    'models' => [
        'hierarchy' => \Cline\Lineage\Database\Hierarchy::class,
    ],

    'table_name' => 'hierarchies',

    'enforceMorphKeyMap' => [
        \App\Models\User::class => 'ulid',
        \App\Models\Seller::class => 'ulid',
        \App\Models\Organization::class => 'ulid',
    ],

    'events' => [
        'enabled' => true,
    ],

    'cache' => [
        'enabled' => true,
        'store' => 'redis',
        'prefix' => 'lineage',
        'ttl' => 3600,
    ],

    'strict' => true,
    'connection' => null,
];
```
