<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Lineage\Exceptions\InvalidConfigurationException;

describe('InvalidConfigurationException', function (): void {
    test('conflictingMorphKeyMaps creates exception with correct message', function (): void {
        $exception = InvalidConfigurationException::conflictingMorphKeyMaps();

        expect($exception)->toBeInstanceOf(InvalidConfigurationException::class);
        expect($exception->getMessage())->toBe(
            'Cannot configure both "morphKeyMap" and "enforceMorphKeyMap". Choose one or the other.',
        );
    });

    test('missingHierarchyType creates exception with correct message', function (): void {
        $exception = InvalidConfigurationException::missingHierarchyType();

        expect($exception)->toBeInstanceOf(InvalidConfigurationException::class);
        expect($exception->getMessage())->toBe('Hierarchy type must be set. Call type() first.');
    });
});
