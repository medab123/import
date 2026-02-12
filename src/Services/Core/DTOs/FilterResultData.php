<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Core\DTOs;

use Spatie\LaravelData\Data;

final class FilterResultData extends Data
{
    public function __construct(
        public array $filteredData,
        public array $originalData,
        public int $totalRows,
        public int $filteredRows,
        public int $excludedRows,
        public array $filterStats = [],
        public array $errors = [],
    ) {}

    public function getFilteredCount(): int
    {
        return $this->filteredRows;
    }

    public function getExcludedCount(): int
    {
        return $this->excludedRows;
    }

    public function getFilterEfficiency(): float
    {
        if ($this->totalRows === 0) {
            return 0.0;
        }

        return round(($this->filteredRows / $this->totalRows) * 100, 2);
    }

    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }

    public function getErrorCount(): int
    {
        return count($this->errors);
    }

    public function getStats(): array
    {
        return array_merge($this->filterStats, [
            'total_rows' => $this->totalRows,
            'filtered_rows' => $this->filteredRows,
            'excluded_rows' => $this->excludedRows,
            'filter_efficiency' => $this->getFilterEfficiency(),
            'error_count' => $this->getErrorCount(),
        ]);
    }
}
