<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Core\Registry;

use Elaitech\Import\Services\Core\Contracts\FactoryInterface;
use Elaitech\Import\Services\Core\Exceptions\FactoryException;
use Illuminate\Contracts\Container\Container;

final class ServiceRegistry
{
    private array $factories = [];

    private array $services = [];

    private ?Container $container = null;

    public function __construct(?Container $container = null)
    {
        $this->container = $container ?? app();
    }

    /**
     * Register a factory for a service type.
     */
    public function registerFactory(string $type, FactoryInterface $factory): void
    {
        $this->factories[$type] = $factory;
    }

    /**
     * Get a factory by type.
     */
    public function getFactory(string $type): FactoryInterface
    {
        if (! isset($this->factories[$type])) {
            throw FactoryException::unsupportedType($type, array_keys($this->factories));
        }

        return $this->factories[$type];
    }

    /**
     * Register a service instance.
     */
    public function registerService(string $name, mixed $service): void
    {
        $this->services[$name] = $service;
    }

    /**
     * Get a service by name.
     */
    public function getService(string $name): mixed
    {
        if (! isset($this->services[$name])) {
            throw new \InvalidArgumentException("Service '{$name}' not found");
        }

        return $this->services[$name];
    }

    /**
     * Get all registered factory types.
     */
    public function getFactoryTypes(): array
    {
        return array_keys($this->factories);
    }

    /**
     * Get all registered service names.
     */
    public function getServiceNames(): array
    {
        return array_keys($this->services);
    }

    /**
     * Check if a factory is registered.
     */
    public function hasFactory(string $type): bool
    {
        return isset($this->factories[$type]);
    }

    /**
     * Check if a service is registered.
     */
    public function hasService(string $name): bool
    {
        return isset($this->services[$name]);
    }

    /**
     * Clear all registrations.
     */
    public function clear(): void
    {
        $this->factories = [];
        $this->services = [];
    }
}
