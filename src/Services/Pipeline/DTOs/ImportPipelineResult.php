<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\DTOs;

use Elaitech\DataMapper\DTO\DataMappingResultData;
use Elaitech\Import\Services\Core\DTOs\DownloadResultData;
use Elaitech\Import\Services\Core\DTOs\FilterResultData;
use Elaitech\Import\Services\Core\DTOs\ReadResultData;
use Spatie\LaravelData\Data;

final class ImportPipelineResult extends Data
{
    public function __construct(
        public DownloadResultData $downloadResult,
        public ?ReadResultData $readResult,
        public DataMappingResultData $mappingResult,
        public ?FilterResultData $filterResult = null,
        public ?PrepareResultData $prepareResult = null,
        public ?SaveResultData $saveResult = null,
        public ImportPipelineStats $stats = new ImportPipelineStats,
        public array $errors = []
    ) {}

    public function hasErrors(): bool
    {
        return ! empty($this->errors) ||
               ($this->readResult && ! empty($this->readResult->errors)) ||
               ! empty($this->mappingResult->errors) ||
               ($this->filterResult && ! empty($this->filterResult->errors)) ||
               ($this->prepareResult && ! empty($this->prepareResult->errors)) ||
               ($this->saveResult && $this->saveResult->hasErrors());
    }

    public function getAllErrors(): array
    {
        $errors = $this->errors;

        if ($this->readResult && ! empty($this->readResult->errors)) {
            $errors = array_merge($errors, $this->readResult->errors);
        }

        if (! empty($this->mappingResult->errors)) {
            $errors = array_merge($errors, $this->mappingResult->errors);
        }

        if ($this->filterResult && ! empty($this->filterResult->errors)) {
            $errors = array_merge($errors, $this->filterResult->errors);
        }

        if ($this->prepareResult && ! empty($this->prepareResult->errors)) {
            $errors = array_merge($errors, array_values($this->prepareResult->errors));
        }

        if ($this->saveResult && $this->saveResult->hasErrors()) {
            $errors = array_merge($errors, array_values($this->saveResult->errors));
        }

        return $errors;
    }

    public function getTotalRows(): int
    {
        return $this->stats->totalRows;
    }

    public function getProcessedRows(): int
    {
        return $this->filterResult ? $this->filterResult->filteredRows : count($this->mappingResult->mappedData);
    }

    public function getSuccessRate(): float
    {
        if ($this->stats->totalRows === 0) {
            return 0.0;
        }

        return ($this->getProcessedRows() / $this->stats->totalRows) * 100;
    }
}
