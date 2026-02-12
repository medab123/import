<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\Pipes;

use Elaitech\Import\Enums\PipelineStage;
use Elaitech\Import\Services\Core\DTOs\ReadResultData;
use Elaitech\Import\Services\Pipeline\DTOs\PipelinePassable;
use Elaitech\Import\Services\Reader\Contracts\ReaderFactoryInterface;
use Closure;
use Psr\Log\LoggerInterface;

/**
 * Read Pipe
 *
 * Handles the read stage of the import pipeline.
 * Reads and parses the downloaded data.
 */
final readonly class ReadPipe
{
    public function __construct(
        private ReaderFactoryInterface $readerFactory,
        private LoggerInterface $logger
    ) {}

    /**
     * Handle the read stage.
     */
    public function handle(PipelinePassable $passable, Closure $next): PipelinePassable
    {
        if ($passable->downloadResult === null) {
            $this->logger->warning('Read stage skipped: no download result available');

            return $passable->withError('Read stage requires download result');
        }

        $stageStart = microtime(true);
        $this->logger->info('Starting read stage', [
            'reader_type' => $passable->config->readerConfig->type,
        ]);

        try {
            $reader = $this->readerFactory->for($passable->config->readerConfig->type);
            $rawData = $reader->read(
                $passable->downloadResult->contents ?? '',
                $passable->config->readerConfig->options
            );

            $readResult = new ReadResultData(
                data: $rawData,
                totalRows: count($rawData),
                readerType: $passable->config->readerConfig->type,
                readStats: [
                    'reader_type' => $passable->config->readerConfig->type,
                ],
                errors: []
            );

            $stageTiming = microtime(true) - $stageStart;
            $this->logger->info('Read stage completed', [
                'reader_type' => $passable->config->readerConfig->type,
                'rows_count' => $readResult->totalRows,
                'duration' => $stageTiming,
            ]);

            $updatedPassable = $passable
                ->withReadResult($readResult)
                ->withCurrentStage(PipelineStage::READ)
                ->withStageMemoryUsage(PipelineStage::READ->name, memory_get_usage(true))
                ->withStageTiming(PipelineStage::READ->name, $stageTiming)
                ->cleanPreviousStage();

            // Stop if target stage is reached
            if ($updatedPassable->shouldStop()) {
                return $updatedPassable;
            }

            return $next($updatedPassable);
        } catch (\Throwable $e) {
            $this->logger->error('Read stage failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $passable->withError("Read failed: {$e->getMessage()}");
        }
    }
}
