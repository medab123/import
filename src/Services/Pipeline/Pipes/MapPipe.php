<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\Pipes;

use Elaitech\Import\Enums\PipelineStage;
use Elaitech\DataMapper\Contracts\DataMapperInterface;
use Elaitech\DataMapper\DTO\MappingConfigurationData;
use Elaitech\Import\Services\Pipeline\DTOs\PipelinePassable;
use Closure;
use Psr\Log\LoggerInterface;

/**
 * Map Pipe
 *
 * Handles the map stage of the import pipeline.
 * Maps raw data to the target structure.
 */
final readonly class MapPipe
{
    public function __construct(
        private DataMapperInterface $dataMapper,
        private LoggerInterface $logger
    ) {}

    /**
     * Handle the map stage.
     */
    public function handle(PipelinePassable $passable, Closure $next): PipelinePassable
    {
        if (empty($passable->filterResult->filteredData)) {
            $this->logger->warning('Map stage skipped: no filtered data available');

            return $passable->withError('Map stage requires filtered data');
        }

        $stageStart = microtime(true);
        $this->logger->info('Starting map stage', [
            'input_rows' => count($passable->filterResult->filteredData),
        ]);

        try {
            $mappingConfig = new MappingConfigurationData(
                data: $passable->filterResult->filteredData,
                mappingRules: $passable->config->mappingConfig->mappingRules,
                headers: $passable->config->mappingConfig->headers
            );

            $mappingResult = $this->dataMapper->map($mappingConfig);

            $stageTiming = microtime(true) - $stageStart;
            $this->logger->info('Map stage completed', [
                'input_rows' => count($passable->filterResult->filteredData),
                'mapped_rows' => count($mappingResult->mappedData),
                'errors' => count($mappingResult->errors),
                'duration' => $stageTiming,
            ]);

            $updatedPassable = $passable
                ->withMappingResult($mappingResult)
                ->withCurrentStage(PipelineStage::MAP)
                ->withStageMemoryUsage(PipelineStage::MAP->name, memory_get_usage(true))
                ->withStageTiming(PipelineStage::MAP->name, $stageTiming)
                ->cleanPreviousStage();

            // Stop if target stage is reached
            if ($updatedPassable->shouldStop()) {
                return $updatedPassable;
            }

            return $next($updatedPassable);
        } catch (\Throwable $e) {
            $this->logger->error('Map stage failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $passable->withError("Map failed: {$e->getMessage()}");
        }
    }
}
