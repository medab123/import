<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\Pipes;

use Elaitech\Import\Enums\ImageDownloadMode;
use Elaitech\Import\Enums\PipelineStage;
use Elaitech\Import\Services\Core\Contracts\ResultSaverInterface;
use Elaitech\Import\Services\Jobs\ImageDownloadJob;
use Elaitech\Import\Services\Pipeline\DTOs\PipelinePassable;
use Elaitech\Import\Services\Pipeline\DTOs\SaveResultData;
use Closure;
use Psr\Log\LoggerInterface;

/**
 * Save Pipe
 *
 * Handles the save stage of the import pipeline.
 * Saves filtered/mapped data to the database using ExtensibleProductFactory.
 */
final readonly class SavePipe
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    /**
     * Handle the save stage.
     */
    public function handle(PipelinePassable $passable, Closure $next): PipelinePassable
    {

        if (empty($passable->prepareResult?->preparedData)) {
            $this->logger->warning('Save stage skipped: no data to save');

            return $passable->withError('Save stage requires mapped data');
        }

        $stageStart = microtime(true);
        $this->logger->info('Starting save stage', [
            'rows_to_save' => count($passable->prepareResult->preparedData),
        ]);

        try {
            $saveResult = $this->saveProducts($passable->prepareResult->preparedData, $passable);
            $stageTiming = microtime(true) - $stageStart;
            $this->logger->info('Save stage completed', [
                'total_processed' => $saveResult->totalProcessed,
                'created' => $saveResult->createdCount,
                'updated' => $saveResult->updatedCount,
                'errors' => $saveResult->errorCount,
                'duration' => $stageTiming,
            ]);

            $updatedPassable = $passable
                ->withSaveResult($saveResult)
                ->withCurrentStage(PipelineStage::SAVE)
                ->withStageMemoryUsage(PipelineStage::SAVE->name, memory_get_usage(true))
                ->withStageTiming(PipelineStage::SAVE->name, $stageTiming)
                ->cleanPreviousStage();

            return $next($updatedPassable);
        } catch (\Throwable $e) {
            $this->logger->error('Save stage failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $passable->withError("Save failed: {$e->getMessage()} {$e->getTraceAsString()}");
        }
    }

    /**
     * Save products to the database.
     *
     * @param  array<int, array<string, mixed>>  $data  Array of product data
     * @param  PipelinePassable  $passable  The pipeline passable containing config
     * @return SaveResultData The save result
     */
    private function saveProducts(array $data, PipelinePassable $passable): SaveResultData
    {
        $createdProducts = [];
        $updatedProducts = [];
        $errors = [];
        $totalProcessed = 0;
        $createdCount = 0;
        $updatedCount = 0;
        $errorCount = 0;
        $targetId = $passable->config->targetId;

        if (! $targetId) {
            throw new \RuntimeException('Target ID is required for saving products. Ensure the import pipeline has a target_id set.');
        }


        $saveUsing = config('import-pipeline.save_using', null);

        if (! $saveUsing) {
            throw new \RuntimeException('No data saver found');
        }

        if (!( $saveUsing instanceof ResultSaverInterface)) {
            throw new \RuntimeException('No data saver found');
        }

       return $saveUsing->save($passable,$targetId);
    }

    /**
     * Determine if images should be downloaded for a product based on configuration.
     *
     * @param  PipelinePassable  $passable  The pipeline passable containing config
     * @param  bool  $isNewProduct  Whether this is a newly created product
     * @param  bool  $hasExistingImages  Whether the product already has images
     * @return bool True if images should be downloaded
     */
    private function shouldDownloadImages(PipelinePassable $passable, bool $isNewProduct, bool $hasExistingImages): bool
    {
        $imagesPrepareConfig = $passable->config->imagesPrepareConfig;

        // If images prepare config is not set or not active, don't download
        if (! $imagesPrepareConfig || ! $imagesPrepareConfig->active) {
            return false;
        }

        return match ($imagesPrepareConfig->downloadMode) {
            ImageDownloadMode::ALL => true,
            ImageDownloadMode::NEW_PRODUCTS_ONLY => $isNewProduct,
            ImageDownloadMode::PRODUCTS_WITHOUT_IMAGES => ! $hasExistingImages,
        };
    }
}
