<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\Services;

use Elaitech\Import\Enums\ImportPipelineStatus;
use App\Events\PipelineExecutionFailedEvent;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Models\ImportPipelineExecution;
use Elaitech\Import\Services\Pipeline\Contracts\PipelineExecutionServiceInterface;
use Elaitech\Import\Services\Pipeline\Contracts\PipelineSchedulingServiceInterface;
use Elaitech\Import\Services\Pipeline\DTOs\ImportPipelineResult;
use Psr\Log\LoggerInterface;
use Throwable;

final class PipelineExecutionService implements PipelineExecutionServiceInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    public function createExecution(ImportPipeline $pipeline): ImportPipelineExecution
    {
        $execution = ImportPipelineExecution::create([
            'pipeline_id' => $pipeline->id,
            'status' => ImportPipelineStatus::PENDING,
            'started_at' => now(),
        ]);

        $this->logger->info('Pipeline execution created', [
            'pipeline_id' => $pipeline->id,
            'execution_id' => $execution->id,
        ]);

        return $execution;
    }

    public function markAsRunning(ImportPipelineExecution $execution): void
    {
        $execution->update([
            'status' => ImportPipelineStatus::RUNNING,
            'started_at' => now(),
        ]);

        $this->logger->info('Pipeline execution started', [
            'execution_id' => $execution->id,
            'pipeline_id' => $execution->pipeline_id,
        ]);
    }

    public function markAsCompleted(ImportPipelineExecution $execution, array $result = []): void
    {
        $execution->update([
            'status' => ImportPipelineStatus::COMPLETED,
            'completed_at' => now(),
            'result_data' => array_merge($execution->result_data ?? [], $result),
        ]);

        $this->logger->info('Pipeline execution completed', [
            'execution_id' => $execution->id,
            'pipeline_id' => $execution->pipeline_id,
            'result' => $result,
        ]);
    }

    public function markAsFailed(ImportPipelineExecution $execution, Throwable $exception): void
    {
        $execution->update([
            'status' => ImportPipelineStatus::FAILED,
            'completed_at' => now(),
            'error_message' => $exception->getMessage(),
        ]);

        $this->logger->error('Pipeline execution failed', [
            'execution_id' => $execution->id,
            'pipeline_id' => $execution->pipeline_id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        event(new PipelineExecutionFailedEvent(
            $execution->pipeline,
            $execution,
            $exception,
            now()
        ));
    }

    public function updateResult(ImportPipelineExecution $execution, ImportPipelineResult $result): void
    {

        $execution->update([
            'total_rows' => $result->getTotalRows(),
            'success_rate' => $result->getSuccessRate(),
            'processed_rows' => $result->getProcessedRows(),
            'processing_time' => $result->stats->processingTime,
            'memory_usage' => $result->stats->getTotalMemoryUsage(),
            'result_data' => [] // $result->toArray(),
        ]);

    }

    public function getLatestRunningExecution(ImportPipeline $pipeline): ?ImportPipelineExecution
    {
        return $pipeline->latestRunningExecution();
    }

    public function updatePipelineExecutionTracking(ImportPipeline $pipeline, ImportPipelineExecution $execution): void
    {
        $pipeline->update([
            'last_executed_at' => $execution->started_at,
        ]);

        // Calculate next execution time if the pipeline is scheduled
        if ($pipeline->isScheduled()) {
            $schedulingService = app(PipelineSchedulingServiceInterface::class);
            $nextExecution = $schedulingService->calculateNextExecution($pipeline);

            if ($nextExecution) {
                $pipeline->update([
                    'next_execution_at' => $nextExecution,
                ]);
            }
        }

        $this->logger->info('Pipeline execution tracking updated', [
            'pipeline_id' => $pipeline->id,
            'execution_id' => $execution->id,
            'last_executed_at' => $pipeline->last_executed_at,
            'next_execution_at' => $pipeline->next_execution_at,
        ]);
    }
}
