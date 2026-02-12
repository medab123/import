<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Core\Exceptions;

use RuntimeException;

final class ReaderException extends RuntimeException
{
    public static function invalidContent(string $readerType, string $reason): self
    {
        return new self("Invalid content for {$readerType} reader: {$reason}");
    }

    public static function parsingFailed(string $readerType, string $reason): self
    {
        return new self("Parsing failed for {$readerType} reader: {$reason}");
    }
}
