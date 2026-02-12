<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Core\DTOs;

use Spatie\LaravelData\Data;

/**
 * Read Result Data
 *
 * Contains the result of the read stage, including parsed raw data
 * and statistics about the reading process.
 */
final class ReadResultData extends Data
{
    /**
     * @param  array<int, array<string, mixed>>  $data  The parsed raw data from the file
     * @param  int  $totalRows  Total number of rows read
     * @param  string  $readerType  The type of reader used
     * @param  array<string, mixed>  $readStats  Statistics about the reading process
     * @param  array<string>  $errors  Array of error messages
     */
    public function __construct(
        public array $data,
        public int $totalRows,
        public string $readerType,
        public array $readStats = [],
        public array $errors = [],
    ) {}

    public function getTotalRows(): int
    {
        return $this->totalRows;
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
        return array_merge($this->readStats, [
            'total_rows' => $this->totalRows,
            'reader_type' => $this->readerType,
            'error_count' => $this->getErrorCount(),
        ]);
    }
}
