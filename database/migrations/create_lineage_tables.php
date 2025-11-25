<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Lineage\Enums\MorphType;
use Cline\Lineage\Enums\PrimaryKeyType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the hierarchies table using the closure table pattern for efficient
     * hierarchical queries. Each row represents a path between an ancestor and
     * descendant with the depth of the relationship.
     */
    public function up(): void
    {
        $tableName = Config::get('lineage.table_name', 'hierarchies');
        $connection = Config::get('lineage.connection');
        $primaryKeyType = PrimaryKeyType::tryFrom(Config::get('lineage.primary_key_type', 'id')) ?? PrimaryKeyType::Id;
        $ancestorMorphType = MorphType::tryFrom(Config::get('lineage.ancestor_morph_type', 'morph')) ?? MorphType::Morph;
        $descendantMorphType = MorphType::tryFrom(Config::get('lineage.descendant_morph_type', 'morph')) ?? MorphType::Morph;

        $schema = $connection ? Schema::connection($connection) : Schema::getFacadeRoot();

        $schema->create($tableName, function (Blueprint $table) use ($primaryKeyType, $ancestorMorphType, $descendantMorphType): void {
            // Primary key based on configuration
            match ($primaryKeyType) {
                PrimaryKeyType::Ulid => $table->ulid('id')->primary(),
                PrimaryKeyType::Uuid => $table->uuid('id')->primary(),
                default => $table->id(),
            };

            // Ancestor polymorphic relationship
            match ($ancestorMorphType) {
                MorphType::UuidMorph => $table->uuidMorphs('ancestor'),
                MorphType::UlidMorph => $table->ulidMorphs('ancestor'),
                default => $table->morphs('ancestor'),
            };

            // Descendant polymorphic relationship
            match ($descendantMorphType) {
                MorphType::UuidMorph => $table->uuidMorphs('descendant'),
                MorphType::UlidMorph => $table->ulidMorphs('descendant'),
                default => $table->morphs('descendant'),
            };

            // Depth of the relationship (0 = self-reference, 1 = direct parent/child, etc.)
            $table->unsignedSmallInteger('depth');

            // Hierarchy type (e.g., 'seller', 'reseller', 'organization')
            $table->string('type', 50)->index();

            $table->timestamps();

            // Composite unique constraint to prevent duplicate paths
            $table->unique(
                ['ancestor_type', 'ancestor_id', 'descendant_type', 'descendant_id', 'type'],
                'hierarchies_path_unique',
            );

            // Indexes for common queries
            $table->index(['descendant_type', 'descendant_id', 'type'], 'hierarchies_descendant_type');
            $table->index(['ancestor_type', 'ancestor_id', 'type'], 'hierarchies_ancestor_type');
            $table->index(['type', 'depth'], 'hierarchies_type_depth');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = Config::get('lineage.table_name', 'hierarchies');
        $connection = Config::get('lineage.connection');

        $schema = $connection ? Schema::connection($connection) : Schema::getFacadeRoot();
        $schema->dropIfExists($tableName);
    }
};
