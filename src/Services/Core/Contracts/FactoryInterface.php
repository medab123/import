<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Core\Contracts;

interface FactoryInterface
{
    /**
     * Register service mappings for the factory.
     */
    public function register(array $services): self;

    /**
     * Get a service instance by type.
     */
    public function for(string $type): mixed;

    /**
     * Get all available service types.
     */
    public function getAvailableTypes(): array;

    /**
     * Check if a service type is supported.
     */
    public function supports(string $type): bool;
}
