<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\DTOs;

use Elaitech\Import\Enums\PipelineStage;
use Elaitech\DataMapper\DTO\DataMappingResultData;
use Elaitech\Import\Services\Core\DTOs\DownloadResultData;
use Elaitech\Import\Services\Core\DTOs\FilterResultData;
use Elaitech\Import\Services\Core\DTOs\ReadResultData;

/**
 * Pipeline Passable
 *
 * Carries state through the Laravel Pipeline, allowing each pipe
 * to access and modify the pipeline state.
 */
final class PipelinePassable
{
    public function __construct(
        public ImportPipelineConfig $config,
        public ?DownloadResultData $downloadResult = null,
        public ?ReadResultData $readResult = null,
        public ?DataMappingResultData $mappingResult = null,
        public ?FilterResultData $filterResult = null,
        public ?PrepareResultData $prepareResult = null,
        public ?SaveResultData $saveResult = null,
        public PipelineStage $currentStage = PipelineStage::DOWNLOAD,
        public ?PipelineStage $targetStage = null,
        public array $stageTimings = [],
        public array $memoryUsage = [],
        public array $errors = [],
        public float $startTime = 0.0,
        public float $startMemoryUsage = 0.0,
    ) {}

    public function withDownloadResult(DownloadResultData $downloadResult): self
    {
        $this->downloadResult = $downloadResult;

        return $this;
    }

    public function withReadResult(ReadResultData $readResult): self
    {
        $this->readResult = $readResult;

        return $this;
    }

    public function withMappingResult(DataMappingResultData $mappingResult): self
    {
        $this->mappingResult = $mappingResult;

        return $this;
    }

    public function withFilterResult(FilterResultData $filterResult): self
    {
        $this->filterResult = $filterResult;

        return $this;
    }

    public function withPrepareResult(PrepareResultData $prepareResult): self
    {
        $this->prepareResult = $prepareResult;

        return $this;
    }

    public function withSaveResult(SaveResultData $saveResult): self
    {
        $this->saveResult = $saveResult;

        return $this;
    }

    public function withCurrentStage(PipelineStage $stage): self
    {
        $this->currentStage = $stage;

        return $this;
    }

    public function withStageTiming(string $stage, float $timing): self
    {
        $this->stageTimings[$stage] = $timing;

        return $this;
    }

    public function withStageMemoryUsage(string $stage, float $memoryUsage): self
    {
        $this->memoryUsage[$stage] = $memoryUsage - $this->startMemoryUsage;

        return $this;
    }

    public function withError(string $error): self
    {
        $this->errors[] = $error;

        return $this;
    }

    public function shouldStop(): bool
    {
        if ($this->targetStage === null) {
            return false;
        }

        return $this->currentStage->order() >= $this->targetStage->order() + 1;
    }

    public function toResult(): ImportPipelineResult
    {
        $processingTime = microtime(true) - $this->startTime;

        $this->memoryUsage['peak'] = memory_get_peak_usage(true) - $this->startMemoryUsage;
        $stats = new ImportPipelineStats(
            totalRows: $this->readResult ? $this->readResult->totalRows : 0,
            mappedRows: $this->mappingResult ? count($this->mappingResult->mappedData) : 0,
            filteredRows: $this->filterResult ? $this->filterResult->filteredRows : ($this->mappingResult ? count($this->mappingResult->mappedData) : 0),
            processingTime: $processingTime,
            stageTimings: $this->stageTimings,
            memoryUsage: $this->memoryUsage,
            errorCount: count($this->getAllErrors())
        );

        return new ImportPipelineResult(
            downloadResult: $this->downloadResult ?? DownloadResultData::from([
                'success' => false,
                'fileSize' => null,
                'filename' => null,
                'mimeType' => null,
                'contents' => null,
            ]),
            readResult: $this->readResult,
            mappingResult: $this->mappingResult ?? new DataMappingResultData([], []),
            filterResult: $this->filterResult,
            prepareResult: $this->prepareResult,
            saveResult: $this->saveResult,
            stats: $stats,
            errors: $this->getAllErrors(),
        );
    }

    private function getAllErrors(): array
    {
        $errors = $this->errors;

        if ($this->readResult && ! empty($this->readResult->errors)) {
            $errors = array_merge($errors, $this->readResult->errors);
        }

        if ($this->mappingResult && ! empty($this->mappingResult->errors)) {
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

    public function cleanPreviousStage(): self
    {
        $previousStage = $this->currentStage->getPreviousStage();
        if (in_array($previousStage, [PipelineStage::DOWNLOAD, null])) {
            return $this;
        }

        match ($previousStage) {
            PipelineStage::READ => $this->downloadResult->contents = null,
            //            PipelineStage::FILTER => $this->readResult->data = [],
            //            PipelineStage::MAP => $this->filterResult->filteredData = [],
            //            PipelineStage::PREPARE => $this->mappingResult->mappedData = [],
            default => null,
        };

        return $this;
    }
}
