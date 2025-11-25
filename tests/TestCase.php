<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests;

use Cline\Lineage\Database\ModelRegistry;
use Cline\Lineage\LineageServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Override;
use Tests\Fixtures\User;

use function env;
use function Orchestra\Testbench\artisan;
use function Orchestra\Testbench\package_path;

/**
 * Base test case for Lineage package tests.
 *
 * @internal
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Setup the world for the tests.
     */
    #[Override()]
    protected function setUp(): void
    {
        parent::setUp();

        Mockery::close();

        // Clear booted models to prevent stale event listeners
        Model::clearBootedModels();

        // Configure morph key map for test models
        $this->app->make(ModelRegistry::class)->morphKeyMap([
            User::class => 'id',
        ]);
    }

    /**
     * Clean up after each test.
     */
    #[Override()]
    protected function tearDown(): void
    {
        // Clear booted models after test to prevent contamination
        Model::clearBootedModels();
        $this->app->make(ModelRegistry::class)->reset();
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Get package providers.
     *
     * @param  mixed                    $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LineageServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param mixed $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('lineage.primary_key_type', env('LINEAGE_PRIMARY_KEY_TYPE', 'id'));
        $app['config']->set('lineage.ancestor_morph_type', env('LINEAGE_ANCESTOR_MORPH_TYPE', 'morph'));
        $app['config']->set('lineage.descendant_morph_type', env('LINEAGE_DESCENDANT_MORPH_TYPE', 'morph'));
        $app['config']->set('lineage.max_depth', 10);
        $app['config']->set('lineage.events.enabled', true);
    }

    /**
     * Define database migrations.
     */
    protected function defineDatabaseMigrations(): void
    {
        artisan($this, 'migrate:install');

        $this->loadMigrationsFrom(package_path('database/migrations'));
        $this->loadMigrationsFrom(__DIR__.'/Fixtures/database/migrations');
    }
}
