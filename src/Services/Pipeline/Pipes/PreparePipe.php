<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\Pipes;

use Elaitech\Import\Enums\PipelineStage;
use Elaitech\Import\Services\Pipeline\Contracts\PrepareServiceInterface;
use Elaitech\Import\Services\Pipeline\DTOs\PipelinePassable;
use Elaitech\Import\Services\Pipeline\DTOs\PrepareConfigurationData;
use Closure;
use Psr\Log\LoggerInterface;

/**
 * Prepare Pipe
 *
 * Handles the prepare stage of the import pipeline.
 * Transforms and prepares data before saving (e.g., category ID resolution,
 * VIN/Stock ID generation, data normalization).
 */
final readonly class PreparePipe
{
    public function __construct(
        private PrepareServiceInterface $prepareService,
        private LoggerInterface $logger
    ) {}

    /**
     * Handle the prepare stage.
     */
    public function handle(PipelinePassable $passable, Closure $next): PipelinePassable
    {
        $dataToPrepare = $this->getDataToPrepare($passable);

        if (empty($dataToPrepare)) {
            $this->logger->warning('Prepare stage skipped: no data to prepare');

            return $passable->withError('Prepare stage requires mapped');
        }

        $stageStart = microtime(true);
        $this->logger->info('Starting prepare stage', [
            'input_rows' => count($dataToPrepare),
        ]);

        try {
            // Use existing prepareConfig or create new one with default transformations
            $prepareConfig = new PrepareConfigurationData(
                data: $dataToPrepare,
                targetId: $passable->config->targetId,
            );

            $prepareResult = $this->prepareService->prepare($prepareConfig);

            $stageTiming = microtime(true) - $stageStart;
            $this->logger->info('Prepare stage completed', [
                'input_rows' => count($dataToPrepare),
                'prepared_rows' => $prepareResult->preparedRows,
                'skipped_rows' => $prepareResult->skippedRows,
                'duration' => $stageTiming,
            ]);

            $updatedPassable = $passable
                ->withPrepareResult($prepareResult)
                ->withCurrentStage(PipelineStage::PREPARE)
                ->withStageMemoryUsage(PipelineStage::PREPARE->name, memory_get_usage(true))
                ->withStageTiming(PipelineStage::PREPARE->name, $stageTiming)
                ->cleanPreviousStage();

            return $next($updatedPassable);
        } catch (\Throwable $e) {
            $this->logger->error('Prepare stage failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $passable->withError("Prepare failed: {$e->getMessage()}");
        }
    }

    /**
     * Get the data to prepare.
     *
     * Priority: filtered data > mapped data > raw data
     *
     * @return array<int, array<string, mixed>>
     */
    private function getDataToPrepare(PipelinePassable $passable): array
    {
        // Fall back to mapped data
        if ($passable->mappingResult !== null && ! empty($passable->mappingResult->mappedData)) {
            return $passable->mappingResult->mappedData;
        }

        return [];
    }
}
