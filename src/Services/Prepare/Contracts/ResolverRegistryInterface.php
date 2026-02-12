<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Prepare\Contracts;

/**
 * Resolver Registry Interface
 *
 * Defines the contract for resolver registries that allow dynamic registration
 * of resolvers for transformation names.
 */
interface ResolverRegistryInterface
{
    /**
     * Register a resolver for a transformation name.
     *
     * @param  string  $transformationName  The transformation name
     * @param  class-string<ResolverInterface>|ResolverInterface  $resolver  The resolver class or instance
     */
    public function register(string $transformationName, string|ResolverInterface $resolver): void;

    /**
     * Get a resolver for a transformation name.
     *
     * @param  string  $transformationName  The transformation name
     * @return ResolverInterface The resolver instance
     *
     * @throws \InvalidArgumentException When resolver is not found
     */
    public function get(string $transformationName): ResolverInterface;

    /**
     * Check if a resolver is registered for a transformation name.
     *
     * @param  string  $transformationName  The transformation name
     * @return bool True if registered, false otherwise
     */
    public function has(string $transformationName): bool;

    /**
     * Get all registered transformation names.
     *
     * @return array<string> Array of transformation names
     */
    public function all(): array;

    /**
     * Remove a resolver registration.
     *
     * @param  string  $transformationName  The transformation name
     */
    public function remove(string $transformationName): void;

    /**
     * Clear all resolver registrations.
     */
    public function clear(): void;
}
