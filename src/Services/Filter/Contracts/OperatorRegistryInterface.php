<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Filter\Contracts;

use Elaitech\Import\Services\Core\Operators\FilterOperatorInterface;

interface OperatorRegistryInterface
{
    /**
     * Register a filter operator.
     */
    public function register(FilterOperatorInterface $operator): void;

    /**
     * Get an operator by name.
     */
    public function get(string $name): FilterOperatorInterface;

    /**
     * Check if an operator exists.
     */
    public function has(string $name): bool;

    /**
     * Get all registered operators.
     *
     * @return array<string, FilterOperatorInterface>
     */
    public function all(): array;

    /**
     * Get operator metadata for UI.
     */
    public function getMetadata(): array;
}
