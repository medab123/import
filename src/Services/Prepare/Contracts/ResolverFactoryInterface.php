<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Prepare\Contracts;

use Elaitech\Import\Services\Core\Contracts\FactoryInterface;

/**
 * Resolver Factory Interface
 *
 * Defines the contract for resolver factories that create resolver instances
 * based on transformation names.
 */
interface ResolverFactoryInterface extends FactoryInterface
{
    /**
     * Get a resolver instance for the given transformation name.
     *
     * @param  string  $type  The transformation name
     * @return ResolverInterface The resolver instance
     */
    public function for(string $type): ResolverInterface;
}
