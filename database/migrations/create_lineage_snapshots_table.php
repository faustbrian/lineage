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
     * Creates the hierarchy_snapshots table for capturing point-in-time hierarchy chains.
     * This enables preserving historical hierarchy relationships even after they change.
     */
    public function up(): void
    {
        $tableName = Config::get('lineage.snapshots.table_name', 'hierarchy_snapshots');
        $connection = Config::get('lineage.connection');
        $primaryKeyType = PrimaryKeyType::tryFrom(Config::get('lineage.primary_key_type', 'id')) ?? PrimaryKeyType::Id;
        $contextMorphType = MorphType::tryFrom(Config::get('lineage.snapshots.context_morph_type', 'morph')) ?? MorphType::Morph;
        $ancestorKeyType = Config::get('lineage.snapshots.ancestor_key_type', 'ulid');

        $schema = $connection ? Schema::connection($connection) : Schema::getFacadeRoot();

        $schema->create($tableName, function (Blueprint $table) use ($primaryKeyType, $contextMorphType, $ancestorKeyType): void {
            // Primary key based on configuration
            match ($primaryKeyType) {
                PrimaryKeyType::Ulid => $table->ulid('id')->primary(),
                PrimaryKeyType::Uuid => $table->uuid('id')->primary(),
                default => $table->id(),
            };

            // Context polymorphic relationship (what the snapshot is for)
            match ($contextMorphType) {
                MorphType::UuidMorph => $table->uuidMorphs('context'),
                MorphType::UlidMorph => $table->ulidMorphs('context'),
                default => $table->morphs('context'),
            };

            // Hierarchy type (e.g., 'seller', 'reseller', 'organization')
            $table->string('type', 50)->index();

            // Depth in the hierarchy chain (0 = direct, 1 = parent, 2 = grandparent, etc.)
            $table->unsignedSmallInteger('depth');

            // Ancestor at this depth level
            match ($ancestorKeyType) {
                'uuid' => $table->uuid('ancestor_id'),
                'ulid' => $table->ulid('ancestor_id'),
                default => $table->unsignedBigInteger('ancestor_id'),
            };

            $table->timestamps();

            // Composite unique constraint - one snapshot per context + type + depth
            $table->unique(
                ['context_type', 'context_id', 'type', 'depth'],
                'hierarchy_snapshots_unique',
            );

            // Indexes for common queries
            $table->index(['context_type', 'context_id', 'type'], 'hierarchy_snapshots_context_type');
            $table->index(['ancestor_id', 'type'], 'hierarchy_snapshots_ancestor_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = Config::get('lineage.snapshots.table_name', 'hierarchy_snapshots');
        $connection = Config::get('lineage.connection');

        $schema = $connection ? Schema::connection($connection) : Schema::getFacadeRoot();
        $schema->dropIfExists($tableName);
    }
};
