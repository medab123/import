<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\Contracts;

use Elaitech\Import\Enums\PipelineStage;
use Elaitech\Import\Services\Pipeline\DTOs\ImportPipelineConfig;
use Elaitech\Import\Services\Pipeline\DTOs\ImportPipelineResult;

interface ImportPipelineInterface
{
    /**
     * Execute the complete import pipeline.
     */
    public function process(ImportPipelineConfig $config): ImportPipelineResult;

    /**
     * Execute pipeline to a specific stage.
     */
    public function executeToStage(ImportPipelineConfig $config, PipelineStage $stage): ImportPipelineResult;

    /**
     * Get available downloader types.
     */
    public function getAvailableDownloader(): array;

    /**
     * Get available reader types.
     */
    public function getAvailableReaders(): array;

    /**
     * Get available filter operators.
     */
    public function getAvailableFilterOperators(): array;

    /**
     * Validate pipeline configuration.
     */
    public function validateConfig(ImportPipelineConfig $config): array;
}
