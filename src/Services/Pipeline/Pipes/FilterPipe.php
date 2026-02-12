<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\Pipes;

use Elaitech\Import\Enums\PipelineStage;
use Elaitech\Import\Services\Core\DTOs\FilterConfigurationData;
use Elaitech\Import\Services\Filter\Contracts\FilterInterface;
use Elaitech\Import\Services\Pipeline\DTOs\PipelinePassable;
use Closure;
use Psr\Log\LoggerInterface;

/**
 * Filter Pipe
 *
 * Handles the filter stage of the import pipeline.
 * Filters mapped data based on configured rules.
 */
final readonly class FilterPipe
{
    public function __construct(
        private FilterInterface $filterService,
        private LoggerInterface $logger
    ) {}

    /**
     * Handle the filter stage.
     */
    public function handle(PipelinePassable $passable, Closure $next): PipelinePassable
    {
        // Filter stage is optional
        if ($passable->config->filterConfig === null) {
            $this->logger->info('Filter stage skipped: no filter configuration');

            return $next($passable);
        }

        if ($passable->readResult === null || empty($passable->readResult->data)) {
            $this->logger->warning('Filter stage skipped: no read data available');

            return $passable->withError('Filter stage requires read data');
        }

        $stageStart = microtime(true);
        $this->logger->info('Starting filter stage', [
            'input_rows' => $passable->readResult->totalRows,
        ]);

        try {
            $filterConfig = new FilterConfigurationData(
                data: $passable->readResult->data,
                filterRules: $passable->config->filterConfig->filterRules,
            );

            $filterResult = $this->filterService->filter($filterConfig);

            $stageTiming = microtime(true) - $stageStart;
            $this->logger->info('Filter stage completed', [
                'input_rows' => $passable->readResult->totalRows,
                'filtered_rows' => $filterResult->filteredRows,
                'excluded_rows' => $filterResult->excludedRows,
                'duration' => $stageTiming,
            ]);

            $updatedPassable = $passable
                ->withFilterResult($filterResult)
                ->withCurrentStage(PipelineStage::FILTER)
                ->withStageMemoryUsage(PipelineStage::FILTER->name, memory_get_usage(true))
                ->withStageTiming(PipelineStage::FILTER->name, $stageTiming)
                ->cleanPreviousStage();

            return $next($updatedPassable);
        } catch (\Throwable $e) {
            $this->logger->error('Filter stage failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $passable->withError("Filter failed: {$e->getMessage()}");
        }
    }
}
