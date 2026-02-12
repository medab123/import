<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\Pipes;

use Elaitech\Import\Enums\PipelineStage;
use Elaitech\Import\Services\Downloader\Contracts\DownloaderFactoryInterface;
use Elaitech\Import\Services\Pipeline\DTOs\PipelinePassable;
use Closure;
use Psr\Log\LoggerInterface;

/**
 * Download Pipe
 *
 * Handles the download stage of the import pipeline.
 * Downloads data from the configured source.
 */
final readonly class DownloadPipe
{
    public function __construct(
        private DownloaderFactoryInterface $downloaderFactory,
        private LoggerInterface $logger
    ) {}

    /**
     * Handle the download stage.
     */
    public function handle(PipelinePassable $passable, Closure $next): PipelinePassable
    {
        $stageStart = microtime(true);
        $this->logger->info('Starting download stage', [
            'source' => $passable->config->downloadRequest->source,
            'downloader_type' => $passable->config->downloadRequest->options['type'] ?? 'https',
        ]);

        try {
            $downloader = $this->downloaderFactory->for(
                $passable->config->downloadRequest->options['type'] ?? 'https'
            );

            $downloadResult = $downloader->download($passable->config->downloadRequest);

            $stageTiming = microtime(true) - $stageStart;
            $this->logger->info('Download stage completed', [
                'filename' => $downloadResult->filename,
                'size' => strlen($downloadResult->contents ?? ''),
                'duration' => $stageTiming,
            ]);

            $updatedPassable = $passable
                ->withDownloadResult($downloadResult)
                ->withCurrentStage(PipelineStage::DOWNLOAD)
                ->withStageMemoryUsage(PipelineStage::DOWNLOAD->name, memory_get_usage(true))
                ->withStageTiming(PipelineStage::DOWNLOAD->name, $stageTiming);

            // Stop if target stage is reached
            if ($updatedPassable->shouldStop()) {
                return $updatedPassable;
            }

            return $next($updatedPassable);
        } catch (\Throwable $e) {
            $this->logger->error('Download stage failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $passable->withError("Download failed: {$e->getMessage()}");
        }
    }
}
