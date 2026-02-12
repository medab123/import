<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Core\Exceptions;

use InvalidArgumentException;

final class InvalidOptionException extends InvalidArgumentException
{
    public function __construct(
        string $option,
        string $expectedType,
        string $actualType,
        string $serviceClass,
        ?string $message = null,
        ?\Throwable $previous = null
    ) {
        $message ??= "Invalid option '{$option}' for {$serviceClass}: expected {$expectedType}, got {$actualType}";

        parent::__construct($message, 0, $previous);
    }
}
