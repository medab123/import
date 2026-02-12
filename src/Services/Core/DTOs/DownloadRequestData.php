<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Core\DTOs;

use Spatie\LaravelData\Data;

final class DownloadRequestData extends Data
{
    public function __construct(
        public string $source,
        public array $options = [],
        public ?string $preferredFilename = null,
        public ?int $connectionTimeout = 1,
        public ?int $timeout = 10
    ) {}
}
