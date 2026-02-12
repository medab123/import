<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Core\Exceptions;

use Exception;

class FilterException extends Exception
{
    public static function unknownOperator(string $operator): self
    {
        return new self("Unknown filter operator: {$operator}");
    }

    public static function invalidRule(string $message): self
    {
        return new self("Invalid filter rule: {$message}");
    }

    public static function regexError(string $pattern, string $error): self
    {
        return new self("Regex error in pattern '{$pattern}': {$error}");
    }

    public static function unsupportedValueType(string $operator, string $valueType): self
    {
        return new self("Operator '{$operator}' does not support value type '{$valueType}'");
    }
}
