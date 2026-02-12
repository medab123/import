<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Core\DTOs;

use Spatie\LaravelData\Data;

final class DownloadResultData extends Data
{
    public function __construct(
        public bool $success,
        public ?string $fileSize,
        public ?string $filename,
        public ?string $mimeType,
        public ?string $contents,
    ) {}
}
