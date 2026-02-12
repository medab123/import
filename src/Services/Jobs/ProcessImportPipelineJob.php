<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Jobs;

use App\Events\PipelineExecutedEvent;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Services\Pipeline\Contracts\ImportPipelineInterface;
use Elaitech\Import\Services\Pipeline\Contracts\PipelineExecutionServiceInterface;
use Elaitech\Import\Services\Pipeline\Contracts\PipelineSchedulingServiceInterface;
use Elaitech\Import\Services\Pipeline\DTOs\ImportPipelineConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;
use Throwable;

final class ProcessImportPipelineJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    public int $maxExceptions;

    public int $timeout;

    private readonly ImportPipelineInterface $pipelineProcessor;

    private readonly PipelineExecutionServiceInterface $executionService;

    private readonly PipelineSchedulingServiceInterface $schedulingService;

    private readonly LoggerInterface $logger;

    public function __construct(
        public readonly ImportPipeline $pipeline,
        public readonly string $triggeredBy = 'scheduler'
    ) {
        $this->tries = config('import-pipelines.retry.max_attempts', 3);
        $this->maxExceptions = config('import-pipelines.retry.max_exceptions', 3);
        $this->timeout = config('import-pipelines.timeouts.default', 3600);

        $this->schedulingService = resolve(PipelineSchedulingServiceInterface::class);
        $this->onQueue(config('import-pipelines.queues.default', 'import-pipelines'));
    }

    public function handle(): void
    {
        $this->logger = resolve(LoggerInterface::class);
        $this->pipelineProcessor = resolve(ImportPipelineInterface::class);
        $this->executionService = resolve(PipelineExecutionServiceInterface::class);

        $execution = $this->executionService->createExecution($this->pipeline);

        try {
            $this->logger->info('Starting import pipeline processing', [
                'pipeline_id' => $this->pipeline->id,
                'execution_id' => $execution->id,
                'target_id' => $this->pipeline->target_id,
                'triggered_by' => $this->triggeredBy,
            ]);

            $this->executionService->markAsRunning($execution);
            $this->processPipeline($execution);
            $this->executionService->markAsCompleted($execution);

            $this->pipeline->update([
                'last_executed_at' => now(),
                'next_execution_at' => $this->schedulingService->calculateNextExecution($this->pipeline),
            ]);

            event(new PipelineExecutedEvent(
                $this->pipeline,
                now(),
                $this->triggeredBy
            ));

        } catch (Throwable $e) {
            $this->executionService->markAsFailed($execution, $e);
            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        $logger = app(LoggerInterface::class);
        $executionService = app(PipelineExecutionServiceInterface::class);

        $logger->error('Import pipeline job failed permanently', [
            'pipeline_id' => $this->pipeline->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $execution = $executionService->getLatestRunningExecution($this->pipeline);

        if ($execution) {
            $executionService->markAsFailed($execution, $exception);
        }
    }

    private function processPipeline($execution): void
    {
        $configs = $this->pipeline->config;
        if ($configs->isEmpty()) {
            throw new \Exception('Pipeline configuration not found');
        }

        $pipelineConfig = ImportPipelineConfig::fromModel($this->pipeline);
        $result = $this->pipelineProcessor->process($pipelineConfig);

        $this->executionService->updateResult($execution, $result);
    }
}
