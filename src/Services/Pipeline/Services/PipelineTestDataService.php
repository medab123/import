<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\Services;

use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Services\Core\DTOs\DownloadRequestData;
use Elaitech\Import\Services\Core\DTOs\DownloadResultData;
use Elaitech\Import\Services\Downloader\Contracts\DownloaderFactoryInterface;
use Elaitech\Import\Services\Reader\Contracts\ReaderFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for fetching and executing pipeline stages for testing purposes.
 */
final readonly class PipelineTestDataService
{
    public function __construct(
        private DownloaderFactoryInterface $downloaderFactory,
        private ReaderFactoryInterface $readerFactory,
        private LoggerInterface $logger
    ) {}

    /**
     * Fetch and download data from pipeline's downloader configuration.
     *
     * @throws \Exception When downloader config is missing or invalid
     */
    public function downloadData(ImportPipeline $pipeline): DownloadResultData
    {
        $downloaderConfig = $pipeline->getDownloaderConfig();

        if (! $downloaderConfig) {
            throw new \Exception('No downloader configuration found. Please configure downloader first.');
        }

        $configData = $downloaderConfig->config_data;
        $downloaderType = $configData['downloader_type'] ?? 'https';
        $downloadOptions = $configData['options'] ?? [];

        if (! isset($downloadOptions['source'])) {
            throw new \Exception('Downloader source is not configured.');
        }

        $this->logger->info('Downloading data for pipeline test', [
            'pipeline_id' => $pipeline->id,
            'downloader_type' => $downloaderType,
        ]);

        $downloader = $this->downloaderFactory->for($downloaderType);
        $downloadRequest = new DownloadRequestData($downloadOptions['source'], $downloadOptions);

        return $downloader->download($downloadRequest);
    }

    /**
     * Read data using pipeline's reader configuration.
     *
     * @throws \Exception When reader config is missing or invalid
     */
    public function readData(ImportPipeline $pipeline, string $content): array
    {
        $readerConfig = $pipeline->getReaderConfig();

        if (! $readerConfig) {
            throw new \Exception('No reader configuration found. Please configure reader first.');
        }

        $configData = $readerConfig->config_data;
        $readerType = $configData['reader_type'] ?? 'csv';
        $readerOptions = $configData['options'] ?? [];

        $this->logger->info('Reading data for pipeline test', [
            'pipeline_id' => $pipeline->id,
            'reader_type' => $readerType,
        ]);

        $reader = $this->readerFactory->for($readerType);

        return $reader->read($content, $readerOptions);
    }

    /**
     * Download and read data in one operation.
     *
     * @return array{downloadResult: DownloadResultData, readData: array}
     *
     * @throws \Exception When configurations are missing or invalid
     */
    public function downloadAndReadData(ImportPipeline $pipeline): array
    {
        $downloadResult = $this->downloadData($pipeline);
        $readData = $this->readData($pipeline, $downloadResult->contents);

        if (empty($readData)) {
            throw new \Exception('No data was read from the source. Please check your reader configuration.');
        }

        return [
            'downloadResult' => $downloadResult,
            'readData' => $readData,
        ];
    }

    /**
     * Get downloader type from pipeline config.
     */
    public function getDownloaderType(ImportPipeline $pipeline): string
    {
        $downloaderConfig = $pipeline->getDownloaderConfig();

        return $downloaderConfig?->config_data['downloader_type'] ?? 'https';
    }

    /**
     * Get reader type from pipeline config.
     */
    public function getReaderType(ImportPipeline $pipeline): string
    {
        $readerConfig = $pipeline->getReaderConfig();

        return $readerConfig?->config_data['reader_type'] ?? 'csv';
    }
}
