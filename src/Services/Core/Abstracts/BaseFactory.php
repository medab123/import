<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Core\Abstracts;

use Elaitech\Import\Services\Core\Contracts\FactoryInterface;
use Elaitech\Import\Services\Core\Exceptions\FactoryException;
use Illuminate\Contracts\Container\Container;

abstract class BaseFactory implements FactoryInterface
{
    protected array $services = [];

    public function __construct(protected Container $container) {}

    public function register(array $services): self
    {
        $this->services = $services;

        return $this;
    }

    public function for(string $type): mixed
    {
        $type = strtolower($type);

        if (! $this->supports($type)) {
            throw FactoryException::unsupportedType($type, $this->getAvailableTypes());
        }

        $serviceClass = $this->services[$type];

        if (! class_exists($serviceClass)) {
            throw FactoryException::classNotFound($serviceClass);
        }

        $this->validateServiceClass($serviceClass);

        return $this->container->make($serviceClass);
    }

    public function getAvailableTypes(): array
    {
        return array_keys($this->services);
    }

    public function supports(string $type): bool
    {
        return isset($this->services[strtolower($type)]);
    }

    /**
     * Validate that the service class implements the expected interface.
     * Override in child classes to specify the expected interface.
     */
    protected function validateServiceClass(string $className): void
    {
        // Override in child classes if specific validation is needed
    }

    /**
     * Get the expected interface for services.
     * Override in child classes to specify the expected interface.
     */
    protected function getExpectedInterface(): ?string
    {
        return null;
    }
}
