<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Lineage;

use Cline\Lineage\Contracts\LineageService as LineageServiceContract;
use Cline\Lineage\Database\ModelRegistry;
use Cline\Lineage\Exceptions\InvalidConfigurationException;
use Illuminate\Support\Facades\Config;
use Override;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

use function is_array;

/**
 * Laravel service provider for Lineage hierarchy package.
 *
 * Handles registration and bootstrapping of Lineage's components including
 * the LineageService, ModelRegistry, configuration, and database migrations.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class LineageServiceProvider extends PackageServiceProvider
{
    /**
     * Configure the package.
     *
     * Defines package configuration including publishable config and migrations.
     *
     * @param Package $package The package instance to configure
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('lineage')
            ->hasConfigFile()
            ->hasMigration('create_lineage_tables');
    }

    /**
     * Register Lineage services in the container.
     *
     * Called during Laravel's service provider registration phase.
     */
    #[Override()]
    public function registeringPackage(): void
    {
        // Bind the contract to the implementation
        $this->app->singleton(
            LineageServiceContract::class,
            LineageService::class,
        );
    }

    /**
     * Bootstrap Lineage services.
     *
     * Called after all service providers have been registered.
     */
    #[Override()]
    public function bootingPackage(): void
    {
        $this->configureMorphKeyMaps();
    }

    /**
     * Configure polymorphic key mappings from configuration.
     *
     * Reads and applies morphKeyMap or enforceMorphKeyMap configuration to control
     * how polymorphic model types are stored in the database. Validates that only
     * one mapping strategy is configured to prevent conflicting behavior.
     *
     * @throws InvalidConfigurationException When both morphKeyMap and enforceMorphKeyMap are configured
     */
    private function configureMorphKeyMaps(): void
    {
        $morphKeyMap = Config::get('lineage.morphKeyMap', []);
        $enforceMorphKeyMap = Config::get('lineage.enforceMorphKeyMap', []);

        if (!is_array($morphKeyMap)) {
            $morphKeyMap = [];
        }

        if (!is_array($enforceMorphKeyMap)) {
            $enforceMorphKeyMap = [];
        }

        $hasMorphKeyMap = $morphKeyMap !== [];
        $hasEnforceMorphKeyMap = $enforceMorphKeyMap !== [];

        if ($hasMorphKeyMap && $hasEnforceMorphKeyMap) {
            throw InvalidConfigurationException::conflictingMorphKeyMaps();
        }

        $registry = $this->app->make(ModelRegistry::class);

        if ($hasEnforceMorphKeyMap) {
            /** @var array<class-string, string> $enforceMorphKeyMap */
            $registry->enforceMorphKeyMap($enforceMorphKeyMap);
        } elseif ($hasMorphKeyMap) {
            /** @var array<class-string, string> $morphKeyMap */
            $registry->morphKeyMap($morphKeyMap);
        }
    }
}
