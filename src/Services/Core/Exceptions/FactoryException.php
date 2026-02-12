<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Core\Exceptions;

use Exception;

class FactoryException extends Exception
{
    public static function unsupportedType(string $type, array $availableTypes): self
    {
        return new self(
            "Unsupported type: '{$type}'. Available types: ".implode(', ', $availableTypes)
        );
    }

    public static function classNotFound(string $className): self
    {
        return new self("Service class does not exist: {$className}");
    }

    public static function invalidServiceClass(string $className, string $expectedInterface): self
    {
        return new self(
            "Service class '{$className}' must implement '{$expectedInterface}'"
        );
    }
}
