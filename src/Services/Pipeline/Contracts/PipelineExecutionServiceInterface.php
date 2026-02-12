<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\Contracts;

use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Models\ImportPipelineExecution;
use Elaitech\Import\Services\Pipeline\DTOs\ImportPipelineResult;
use Throwable;

interface PipelineExecutionServiceInterface
{
    public function createExecution(ImportPipeline $pipeline): ImportPipelineExecution;

    public function markAsRunning(ImportPipelineExecution $execution): void;

    public function markAsCompleted(ImportPipelineExecution $execution, array $result = []): void;

    public function markAsFailed(ImportPipelineExecution $execution, Throwable $exception): void;

    public function updateResult(ImportPipelineExecution $execution, ImportPipelineResult $result): void;

    public function getLatestRunningExecution(ImportPipeline $pipeline): ?ImportPipelineExecution;

    public function updatePipelineExecutionTracking(ImportPipeline $pipeline, ImportPipelineExecution $execution): void;
}
