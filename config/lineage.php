<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*
|--------------------------------------------------------------------------
| Lineage Hierarchy Configuration
|--------------------------------------------------------------------------
|
| This file defines the configuration for Lineage, a Laravel package that
| provides closure table hierarchies for Eloquent models. The closure table
| pattern enables O(1) queries for ancestors and descendants without recursion
| limits, supporting deeply nested relationships like organizational charts,
| sales hierarchies, and category trees.
|
*/

use Cline\Lineage\Database\Hierarchy;

return [
    /*
    |--------------------------------------------------------------------------
    | Primary Key Type
    |--------------------------------------------------------------------------
    |
    | This option controls the type of primary key used in Lineage's database
    | tables. You may use traditional auto-incrementing integers or choose
    | ULIDs or UUIDs for distributed systems or enhanced privacy.
    |
    | Supported: "id", "ulid", "uuid"
    |
    */

    'primary_key_type' => env('LINEAGE_PRIMARY_KEY_TYPE', 'id'),

    /*
    |--------------------------------------------------------------------------
    | Ancestor Morph Type
    |--------------------------------------------------------------------------
    |
    | This option controls the type of polymorphic relationship columns used
    | for ancestor relationships in Lineage's database tables. This determines
    | how ancestors are stored in the closure table.
    |
    | Supported: "morph", "uuidMorph", "ulidMorph"
    |
    */

    'ancestor_morph_type' => env('LINEAGE_ANCESTOR_MORPH_TYPE', 'morph'),

    /*
    |--------------------------------------------------------------------------
    | Descendant Morph Type
    |--------------------------------------------------------------------------
    |
    | This option controls the type of polymorphic relationship columns used
    | for descendant relationships in Lineage's database tables. This determines
    | how descendants are stored in the closure table.
    |
    | Supported: "morph", "uuidMorph", "ulidMorph"
    |
    */

    'descendant_morph_type' => env('LINEAGE_DESCENDANT_MORPH_TYPE', 'morph'),

    /*
    |--------------------------------------------------------------------------
    | Maximum Hierarchy Depth
    |--------------------------------------------------------------------------
    |
    | This option controls the maximum depth allowed for hierarchies. This
    | prevents infinite recursion and ensures reasonable performance. A depth
    | of 10 supports most organizational structures whilst preventing abuse.
    | Set to null for unlimited depth (not recommended for production).
    |
    */

    'max_depth' => env('LINEAGE_MAX_DEPTH', 10),

    /*
    |--------------------------------------------------------------------------
    | Eloquent Models
    |--------------------------------------------------------------------------
    |
    | Lineage needs to know which Eloquent model should be used to interact
    | with the hierarchies database table. You may extend this model with
    | your own implementation whilst ensuring it extends the base class
    | provided by Lineage.
    |
    */

    'models' => [
        /*
        |--------------------------------------------------------------------------
        | Hierarchy Model
        |--------------------------------------------------------------------------
        |
        | This model is used to retrieve hierarchy entries from the database.
        | The model you specify must extend the `Cline\Lineage\Database\Hierarchy`
        | class. This allows you to customise the hierarchy model behaviour whilst
        | maintaining compatibility with Lineage's internal operations.
        |
        */

        'hierarchy' => Hierarchy::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Table Name
    |--------------------------------------------------------------------------
    |
    | Lineage needs to know which table name should be used to store your
    | hierarchy relationships. This table name is used by both the migration
    | and the Eloquent model.
    |
    */

    'table_name' => env('LINEAGE_TABLE', 'hierarchies'),

    /*
    |--------------------------------------------------------------------------
    | Polymorphic Key Mapping
    |--------------------------------------------------------------------------
    |
    | This option allows you to specify which column should be used as the
    | foreign key for each model in polymorphic relationships. This is
    | particularly useful when different models in your application use
    | different primary key column names, which is common in legacy systems
    | or when using ULIDs and UUIDs alongside traditional auto-incrementing
    | integer keys.
    |
    | For example, if your User model uses 'id' but your Organization model
    | uses 'ulid', you can map each model to its appropriate key column here.
    | Lineage will then use the correct column when storing foreign keys.
    |
    | Note: You may only configure either 'morphKeyMap' or 'enforceMorphKeyMap',
    | not both. Choose the non-enforced variant if you want to allow models
    | without explicit mappings to use their default primary key.
    |
    */

    'morphKeyMap' => [
        // App\Models\User::class => 'id',
        // App\Models\Organization::class => 'ulid',
    ],

    /*
    |--------------------------------------------------------------------------
    | Enforced Polymorphic Key Mapping
    |--------------------------------------------------------------------------
    |
    | This option works identically to 'morphKeyMap' above, but enables strict
    | enforcement of your key mappings. When configured, any model referenced
    | in a polymorphic relationship without an explicit mapping defined here
    | will throw a MorphKeyViolationException.
    |
    | This enforcement is useful in production environments where you want to
    | ensure all models participating in polymorphic relationships have been
    | explicitly configured, preventing potential bugs from unmapped models.
    |
    | Note: Only configure either 'morphKeyMap' or 'enforceMorphKeyMap'. Using
    | both simultaneously is not supported. Choose this enforced variant when
    | you want strict type safety for your polymorphic relationships.
    |
    */

    'enforceMorphKeyMap' => [
        // App\Models\User::class => 'id',
        // App\Models\Organization::class => 'ulid',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hierarchy Types
    |--------------------------------------------------------------------------
    |
    | Here you may define the hierarchy types available in your application.
    | Each type represents a different kind of hierarchical relationship
    | (e.g., seller hierarchies, category trees, organizational charts).
    |
    | You may define types as a simple array of strings, or as an enum class
    | that implements the HierarchyTypeContract interface. Using an enum
    | provides type safety and IDE autocompletion.
    |
    | Simple array: ['seller', 'reseller', 'organization']
    | Enum class: App\Enums\HierarchyType::class
    |
    */

    'types' => [
        // 'seller',
        // 'reseller',
        // 'organization',
    ],

    /*
    |--------------------------------------------------------------------------
    | Type Enum Class
    |--------------------------------------------------------------------------
    |
    | If you prefer to use a backed enum for hierarchy types instead of simple
    | strings, specify the fully-qualified class name here. The enum must be
    | a backed string enum. When set, this takes precedence over the 'types'
    | array above.
    |
    | Example: App\Enums\HierarchyType::class
    |
    */

    'type_enum' => env('LINEAGE_TYPE_ENUM'),

    /*
    |--------------------------------------------------------------------------
    | Events Configuration
    |--------------------------------------------------------------------------
    |
    | Configure event dispatching behavior for hierarchy operations.
    |
    */

    'events' => [
        /*
        |--------------------------------------------------------------------------
        | Events Enabled
        |--------------------------------------------------------------------------
        |
        | When true, Lineage will dispatch events during hierarchy operations.
        | This enables event-driven workflows such as logging, notifications,
        | or automated responses to hierarchy changes.
        |
        | Events dispatched:
        | - NodeAttached: When a node is attached to a parent
        | - NodeDetached: When a node is detached from its parent
        | - NodeMoved: When a node is moved to a new parent
        | - NodeRemoved: When a node is completely removed from a hierarchy
        |
        */

        'enabled' => env('LINEAGE_EVENTS_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for hierarchy queries. Caching can significantly
    | improve performance for read-heavy workloads where hierarchy structures
    | don't change frequently.
    |
    */

    'cache' => [
        /*
        |--------------------------------------------------------------------------
        | Cache Enabled
        |--------------------------------------------------------------------------
        |
        | When true, Lineage will cache hierarchy query results. This is
        | recommended for production environments where hierarchies are read
        | frequently but modified infrequently.
        |
        */

        'enabled' => env('LINEAGE_CACHE_ENABLED', false),

        /*
        |--------------------------------------------------------------------------
        | Cache Store
        |--------------------------------------------------------------------------
        |
        | Specify which cache store should be used for hierarchy caching. If
        | null, the default cache store will be used. Consider using Redis
        | or Memcached for optimal performance in distributed systems.
        |
        */

        'store' => env('LINEAGE_CACHE_STORE'),

        /*
        |--------------------------------------------------------------------------
        | Cache Key Prefix
        |--------------------------------------------------------------------------
        |
        | This prefix is prepended to all cache keys used by Lineage. This
        | helps prevent collisions with other cached data in your application.
        |
        */

        'prefix' => env('LINEAGE_CACHE_PREFIX', 'lineage'),

        /*
        |--------------------------------------------------------------------------
        | Cache TTL
        |--------------------------------------------------------------------------
        |
        | The time-to-live (in seconds) for cached hierarchy data. After this
        | duration, cached data will be invalidated and re-fetched from the
        | database. The default is 3600 seconds (1 hour).
        |
        */

        'ttl' => env('LINEAGE_CACHE_TTL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Strict Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, Lineage will throw exceptions for potentially problematic
    | operations instead of silently handling them. This is recommended for
    | development and staging environments to catch issues early.
    |
    | Strict mode enforces:
    | - Circular reference detection with detailed error messages
    | - Depth limit violations with clear exceptions
    | - Type mismatches in hierarchy operations
    |
    */

    'strict' => env('LINEAGE_STRICT', true),

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | You may specify a different database connection for Lineage's tables.
    | This is useful if you want to isolate hierarchy data in a separate
    | database. If null, the default database connection will be used.
    |
    */

    'connection' => env('LINEAGE_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Hierarchy Snapshots Configuration
    |--------------------------------------------------------------------------
    |
    | Snapshots capture point-in-time hierarchy chains, preserving historical
    | relationships even when hierarchies change. This is useful for maintaining
    | audit trails, commission calculations, or any scenario where you need to
    | know what the hierarchy looked like at a specific moment.
    |
    */

    'snapshots' => [
        /*
        |--------------------------------------------------------------------------
        | Snapshots Enabled
        |--------------------------------------------------------------------------
        |
        | When true, snapshot functionality will be available. You can disable
        | this if you don't need snapshot capabilities in your application.
        |
        */

        'enabled' => env('LINEAGE_SNAPSHOTS_ENABLED', true),

        /*
        |--------------------------------------------------------------------------
        | Snapshot Model
        |--------------------------------------------------------------------------
        |
        | This model is used to retrieve hierarchy snapshot entries from the
        | database. The model you specify must extend the base HierarchySnapshot
        | class provided by Lineage.
        |
        */

        'model' => \Cline\Lineage\Database\HierarchySnapshot::class,

        /*
        |--------------------------------------------------------------------------
        | Snapshots Table Name
        |--------------------------------------------------------------------------
        |
        | The database table used to store hierarchy snapshots.
        |
        */

        'table_name' => env('LINEAGE_SNAPSHOTS_TABLE', 'hierarchy_snapshots'),

        /*
        |--------------------------------------------------------------------------
        | Context Morph Type
        |--------------------------------------------------------------------------
        |
        | This controls the type of polymorphic relationship columns used for
        | the context (the model that the snapshot is attached to).
        |
        | Supported: "morph", "uuidMorph", "ulidMorph"
        |
        */

        'context_morph_type' => env('LINEAGE_SNAPSHOTS_CONTEXT_MORPH_TYPE', 'morph'),

        /*
        |--------------------------------------------------------------------------
        | Ancestor Key Type
        |--------------------------------------------------------------------------
        |
        | The type of key used for the ancestor_id column in snapshots.
        |
        | Supported: "id", "ulid", "uuid"
        |
        */

        'ancestor_key_type' => env('LINEAGE_SNAPSHOTS_ANCESTOR_KEY_TYPE', 'ulid'),
    ],
];
