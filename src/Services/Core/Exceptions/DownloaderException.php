<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Core\Exceptions;

use RuntimeException;

final class DownloaderException extends RuntimeException
{
    public static function connectionFailed(string $downloaderType, string $reason): self
    {
        return new self("Connection failed for {$downloaderType} downloader: {$reason}");
    }

    public static function fileNotFound(string $downloaderType, string $path): self
    {
        return new self("File not found for {$downloaderType} downloader: {$path}");
    }

    public static function downloadFailed(string $downloaderType, string $reason): self
    {
        return new self("Download failed for {$downloaderType} downloader: {$reason}");
    }
}
