<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\Orchestrators;

use Elaitech\Import\Enums\PipelineStage;
use Elaitech\Import\Services\Pipeline\DTOs\ImportPipelineConfig;
use Elaitech\Import\Services\Pipeline\DTOs\ImportPipelineResult;
use Elaitech\Import\Services\Pipeline\DTOs\PipelinePassable;
use Elaitech\Import\Services\Pipeline\Pipes\DownloadPipe;
use Elaitech\Import\Services\Pipeline\Pipes\FilterPipe;
use Elaitech\Import\Services\Pipeline\Pipes\ImagesPreparePipe;
use Elaitech\Import\Services\Pipeline\Pipes\MapPipe;
use Elaitech\Import\Services\Pipeline\Pipes\PreparePipe;
use Elaitech\Import\Services\Pipeline\Pipes\ReadPipe;
use Elaitech\Import\Services\Pipeline\Pipes\SavePipe;
use Illuminate\Pipeline\Pipeline;
use Psr\Log\LoggerInterface;

/**
 * Pipeline Orchestrator
 *
 * Orchestrates the import pipeline using Laravel's Pipeline pattern.
 * Allows execution to any specific stage with proper state management.
 */
final readonly class PipelineOrchestrator
{
    public function __construct(
        private Pipeline $pipeline,
        private DownloadPipe $downloadPipe,
        private ReadPipe $readPipe,
        private MapPipe $mapPipe,
        private FilterPipe $filterPipe,
        private ImagesPreparePipe $imagesPreparePipe,
        private PreparePipe $preparePipe,
        private SavePipe $savePipe,
        private LoggerInterface $logger
    ) {}

    /**
     * Execute the pipeline to a specific stage.
     *
     * @param  ImportPipelineConfig  $config  The pipeline configuration
     * @param  PipelineStage|null  $targetStage  The target stage to execute to (null = all stages)
     * @return ImportPipelineResult The pipeline result
     */
    public function execute(ImportPipelineConfig $config, ?PipelineStage $targetStage = null): ImportPipelineResult
    {
        $startTime = microtime(true);

        $this->logger->info('Starting pipeline execution', [
            'target_stage' => $targetStage?->value ?? 'all',
            'source' => $config->downloadRequest->source,
        ]);

        $passable = new PipelinePassable(
            config: $config,
            currentStage: PipelineStage::DOWNLOAD,
            targetStage: $targetStage,
            startTime: $startTime,
        );

        $passable->startMemoryUsage = memory_get_usage(true);

        $pipes = $this->getPipesForStage($targetStage);

        /** @var PipelinePassable $result */
        $result = $this->pipeline
            ->send($passable)
            ->through($pipes)
            ->then(fn (PipelinePassable $passable) => $passable);

        $this->logger->info('Pipeline execution completed', [
            'final_stage' => $result->currentStage->value,
            'duration' => microtime(true) - $startTime,
        ]);

        return $result->toResult();
    }

    /**
     * Execute all pipeline stages.
     */
    public function executeAll(ImportPipelineConfig $config): ImportPipelineResult
    {
        return $this->execute($config);
    }

    /**
     * Get pipes to execute based on target stage.
     *
     * @param  PipelineStage|null  $targetStage  The target stage
     * @return array<int, class-string|object> Array of pipe classes or instances
     */
    private function getPipesForStage(?PipelineStage $targetStage): array
    {
        $allPipes = [
            $this->downloadPipe,
            $this->readPipe,
            $this->filterPipe,
            $this->mapPipe,
            $this->imagesPreparePipe,
            $this->preparePipe,
            $this->savePipe,
        ];

        if ($targetStage === null) {
            return $allPipes;
        }

        return array_slice($allPipes, 0, $targetStage->order());
    }
}
