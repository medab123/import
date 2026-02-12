<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\Pipes;

use Elaitech\Import\Enums\PipelineStage;
use Elaitech\DataMapper\DTO\DataMappingResultData;
use Elaitech\Import\Services\Pipeline\DTOs\ImagesPrepareConfigurationData;
use Elaitech\Import\Services\Pipeline\DTOs\PipelinePassable;
use Closure;
use Psr\Log\LoggerInterface;

/**
 * Images Prepare Pipe
 *
 * Handles the images prepare stage of the import pipeline.
 * Processes images from mapped data by:
 * - Exploding image strings using the configured separator
 * - Removing images at specified indexes
 */
final readonly class ImagesPreparePipe
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    /**
     * Handle the images prepare stage.
     */
    public function handle(PipelinePassable $passable, Closure $next): PipelinePassable
    {
        // Images prepare stage is optional
        if ($passable->config->imagesPrepareConfig === null) {
            $this->logger->info('Images prepare stage skipped: no images prepare configuration');

            return $next($passable);
        }

        if ($passable->mappingResult === null || empty($passable->mappingResult->mappedData)) {
            $this->logger->warning('Images prepare stage skipped: no mapped data available');

            return $passable->withError('Images prepare stage requires mapped data');
        }

        $stageStart = microtime(true);
        $this->logger->info('Starting images prepare stage', [
            'input_rows' => count($passable->mappingResult->mappedData),
            'separator' => $passable->config->imagesPrepareConfig->imageSeparator,
            'indexes_to_skip' => $passable->config->imagesPrepareConfig->imageIndexesToSkip,
        ]);

        try {
            $processedData = $this->processImages(
                $passable->mappingResult->mappedData,
                $passable->config->imagesPrepareConfig
            );

            // Create updated mapping result with processed data
            $updatedMappingResult = new DataMappingResultData(
                mappedData: $processedData,
                errors: $passable->mappingResult->errors
            );

            $stageTiming = microtime(true) - $stageStart;
            $this->logger->info('Images prepare stage completed', [
                'input_rows' => count($passable->mappingResult->mappedData),
                'processed_rows' => count($processedData),
                'duration' => $stageTiming,
            ]);

            $updatedPassable = $passable
                ->withMappingResult($updatedMappingResult)
                ->withCurrentStage(PipelineStage::IMAGES_PREPARE)
                ->withStageMemoryUsage(PipelineStage::IMAGES_PREPARE->name, memory_get_usage(true))
                ->withStageTiming(PipelineStage::IMAGES_PREPARE->name, $stageTiming)
                ->cleanPreviousStage();

            // Stop if target stage is reached
            if ($updatedPassable->shouldStop()) {
                return $updatedPassable;
            }

            return $next($updatedPassable);
        } catch (\Throwable $e) {
            $this->logger->error('Images prepare stage failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $passable->withError("Images prepare failed: {$e->getMessage()}");
        }
    }

    /**
     * Process images for each product in the mapped data.
     *
     * @param  array<int, array<string, mixed>>  $data  The mapped data
     * @param  ImagesPrepareConfigurationData  $config  The images prepare configuration
     * @return array<int, array<string, mixed>> Processed data with images prepared
     */
    private function processImages(
        array $data,
        ImagesPrepareConfigurationData $config
    ): array {
        $processedData = [];

        foreach ($data as $product) {
            $processedProduct = $product;
            if (isset($product[$config->imagesKey]) && is_string($product[$config->imagesKey])) {
                $imagesString = $product[$config->imagesKey];
                $images = $this->explodeImages($imagesString, $config->imageSeparator);
                $filteredImages = $this->removeSkippedIndexes($images, $config->imageIndexesToSkip);
                $processedProduct[$config->imagesKey] = $filteredImages;
            } elseif (isset($product[$config->imagesKey]) && is_array($product[$config->imagesKey])) {
                $filteredImages = $this->removeSkippedIndexes(
                    $product[$config->imagesKey],
                    $config->imageIndexesToSkip
                );
                $processedProduct[$config->imagesKey] = $filteredImages;
            }

            $processedData[] = $processedProduct;
        }

        return $processedData;
    }

    /**
     * Explode images string into an array.
     *
     * @param  string  $imagesString  The images string
     * @param  string  $separator  The separator character(s)
     * @return array<int, string> Array of image URLs
     */
    private function explodeImages(string $imagesString, string $separator): array
    {
        if (empty($imagesString)) {
            return [];
        }

        if (empty($separator)) {
            return [$imagesString];
        }

        $images = explode($separator, $imagesString);

        // Trim whitespace from each image URL
        return array_map('trim', $images);
    }

    /**
     * Remove images at specified indexes.
     *
     * @param  array<int, string>  $images  Array of image URLs
     * @param  array<int>  $indexesToSkip  Array of indexes to skip (0-based)
     * @return array<int, string> Filtered array of image URLs (re-indexed)
     */
    private function removeSkippedIndexes(array $images, array $indexesToSkip): array
    {
        if (empty($indexesToSkip)) {
            return array_values($images);
        }

        // Filter out images at skipped indexes and re-index
        $filtered = [];
        foreach ($images as $index => $image) {
            if (! in_array($index, $indexesToSkip, true)) {
                $filtered[] = $image;
            }
        }

        return $filtered;
    }
}
