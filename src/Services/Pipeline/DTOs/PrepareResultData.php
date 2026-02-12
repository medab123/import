<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\DTOs;

use Spatie\LaravelData\Data;

/**
 * Prepare Result Data
 *
 * Contains the result of the prepare stage, including transformed data
 * and statistics about the preparation process.
 */
final class PrepareResultData extends Data
{
    /**
     * @param  array<int, array<string, mixed>>  $preparedData  The prepared/transformed data ready for saving
     * @param  array<int, array<string, mixed>>  $originalData  The original data before preparation
     * @param  int  $totalRows  Total number of rows processed
     * @param  int  $preparedRows  Number of rows successfully prepared
     * @param  int  $skippedRows  Number of rows skipped during preparation
     * @param  array<string, mixed>  $transformationStats  Statistics about transformations applied
     * @param  array<string, string>  $errors  Array of row index => error message
     */
    public function __construct(
        public array $preparedData,
        public array $originalData,
        public int $totalRows,
        public int $preparedRows,
        public int $skippedRows,
        public array $transformationStats = [],
        public array $errors = [],
    ) {}

    public function getPreparedCount(): int
    {
        return $this->preparedRows;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedRows;
    }

    public function getPreparationEfficiency(): float
    {
        if ($this->totalRows === 0) {
            return 0.0;
        }

        return round(($this->preparedRows / $this->totalRows) * 100, 2);
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
        return array_merge($this->transformationStats, [
            'total_rows' => $this->totalRows,
            'prepared_rows' => $this->preparedRows,
            'skipped_rows' => $this->skippedRows,
            'preparation_efficiency' => $this->getPreparationEfficiency(),
            'error_count' => $this->getErrorCount(),
        ]);
    }
}
