<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\DTOs;

use Spatie\LaravelData\Data;

final class ImportPipelineOptions extends Data
{
    public function __construct(
        public bool $enableCaching = true,
        public bool $enableLogging = true,
        public bool $stopOnError = false,
        public ?string $cacheKey = null,
        public int $timeout = 300,
        public bool $enableMetrics = true
    ) {}
}
