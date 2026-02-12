<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\DTOs;

use Spatie\LaravelData\Data;

final class ImportPipelineStats extends Data
{
    public function __construct(
        public int $totalRows = 0,
        public int $mappedRows = 0,
        public int $filteredRows = 0,
        public float $processingTime = 0.0,
        public array $stageTimings = [],
        public array $memoryUsage = [],
        public int $errorCount = 0
    ) {}

    public function getTotalMemoryUsage(): int
    {
        return count($this->memoryUsage)
            ? (int) (array_sum($this->memoryUsage) / count($this->memoryUsage))
            : 0;
    }
}
