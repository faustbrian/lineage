<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Lineage\Contracts;

/**
 * Contract for hierarchy type implementations.
 *
 * Implement this interface on your backed string enum to provide type-safe
 * hierarchy types in your application. The enum must be a backed string enum.
 *
 * Example:
 * ```php
 * enum HierarchyType: string implements HierarchyTypeContract
 * {
 *     case Seller = 'seller';
 *     case Reseller = 'reseller';
 *     case Organization = 'organization';
 *
 *     public function value(): string
 *     {
 *         return $this->value;
 *     }
 * }
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface HierarchyType
{
    /**
     * Get the string value of the hierarchy type.
     */
    public function value(): string;
}
