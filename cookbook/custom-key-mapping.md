# Custom Key Mapping

Lineage supports custom polymorphic key mappings, allowing you to use any column as the identifier in hierarchy relationships instead of the default primary key.

## Why Custom Key Mapping?

By default, Lineage uses each model's primary key (`id`) to store ancestor/descendant relationships. However, you may need to:

- Use UUIDs or ULIDs stored in a different column
- Reference models by a unique business identifier (e.g., `email`, `slug`)
- Support mixed key types across different models

## Configuration

### morphKeyMap (Optional Mapping)

Define key mappings in `config/lineage.php`:

```php
'morphKeyMap' => [
    \App\Models\User::class => 'uuid',
    \App\Models\Seller::class => 'ulid',
    \App\Models\Organization::class => 'external_id',
],
```

Models not in the map fall back to their default primary key.

### enforceMorphKeyMap (Strict Mapping)

For stricter control, use `enforceMorphKeyMap` to require all models be explicitly mapped:

```php
'enforceMorphKeyMap' => [
    \App\Models\User::class => 'uuid',
    \App\Models\Seller::class => 'ulid',
],
```

Using an unmapped model throws `MorphKeyViolationException`:

```php
use App\Models\Post;

// Throws MorphKeyViolationException: Model [App\Models\Post] is not mapped
Lineage::addToHierarchy($post, 'category');
```

**Note:** Configure either `morphKeyMap` or `enforceMorphKeyMap`, not both.

## Programmatic Configuration

You can also configure mappings at runtime via `ModelRegistry`:

```php
use Cline\Lineage\Database\ModelRegistry;

$registry = app(ModelRegistry::class);

// Optional mapping
$registry->morphKeyMap([
    User::class => 'email',
]);

// Strict mapping
$registry->enforceMorphKeyMap([
    User::class => 'email',
    Seller::class => 'ulid',
]);

// Enable strict mode separately
$registry->morphKeyMap([User::class => 'email']);
$registry->requireKeyMap();
```

## Example: Using Email as Key

```php
// config/lineage.php
'morphKeyMap' => [
    \App\Models\User::class => 'email',
],
```

```php
$manager = User::create(['name' => 'Manager', 'email' => 'manager@company.com']);
$seller = User::create(['name' => 'Seller', 'email' => 'seller@company.com']);

Lineage::addToHierarchy($manager, 'sales');
Lineage::addToHierarchy($seller, 'sales', $manager);

// Database stores 'manager@company.com' and 'seller@company.com' as the IDs
// Queries automatically use the email column
$ancestors = Lineage::getAncestors($seller, 'sales');
// Returns the manager User model
```

## Example: Mixed Key Types

Different models can use different key columns:

```php
'morphKeyMap' => [
    \App\Models\User::class => 'uuid',      // Users identified by UUID
    \App\Models\Team::class => 'slug',      // Teams identified by slug
    \App\Models\Department::class => 'id',  // Departments use standard ID
],
```

```php
$team = Team::create(['name' => 'Sales', 'slug' => 'sales-team']);
$user = User::create(['name' => 'John', 'uuid' => 'abc-123']);

Lineage::addToHierarchy($team, 'organization');
Lineage::addToHierarchy($user, 'organization', $team);

// team's slug 'sales-team' stored as ancestor_id
// user's uuid 'abc-123' stored as descendant_id
```

## Database Considerations

### Column Types

When using custom keys, ensure your morph type configuration matches:

| Key Type | Recommended Morph Type |
|----------|----------------------|
| Integer IDs | `morph` or `numericMorph` |
| UUIDs | `uuidMorph` |
| ULIDs | `ulidMorph` |
| Strings (email, slug) | `morph` (varchar) |

```php
// config/lineage.php
'ancestor_morph_type' => 'morph',    // varchar - flexible for any string
'descendant_morph_type' => 'morph',
```

### Indexing

Add indexes on your custom key columns for optimal query performance:

```php
Schema::table('users', function (Blueprint $table) {
    $table->index('email');
    $table->index('uuid');
});
```

## Testing with Custom Keys

Reset the registry between tests to prevent state leakage:

```php
use Cline\Lineage\Database\ModelRegistry;

beforeEach(function () {
    app(ModelRegistry::class)->reset();
});

test('uses custom key mapping', function () {
    app(ModelRegistry::class)->morphKeyMap([
        User::class => 'email',
    ]);
    
    $parent = User::create(['email' => 'parent@test.com']);
    $child = User::create(['email' => 'child@test.com']);
    
    Lineage::addToHierarchy($parent, 'test');
    Lineage::addToHierarchy($child, 'test', $parent);
    
    expect(Lineage::getAncestors($child, 'test'))->toHaveCount(1);
});
```

## Error Handling

```php
use Cline\Lineage\Exceptions\MorphKeyViolationException;

try {
    Lineage::addToHierarchy($unmappedModel, 'hierarchy');
} catch (MorphKeyViolationException $e) {
    // Handle unmapped model when enforcement is enabled
    Log::warning($e->getMessage());
}
```
