<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Prepare\Services;

use Elaitech\Import\Services\Pipeline\Contracts\PrepareServiceInterface;
use Elaitech\Import\Services\Pipeline\DTOs\PrepareConfigurationData;
use Elaitech\Import\Services\Pipeline\DTOs\PrepareResultData;
use Elaitech\Import\Services\Prepare\Contracts\ResolverInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Data Prepare Service
 *
 * Prepares and transforms data before saving, including:
 * - Category ID resolution
 * - VIN/Stock ID generation
 * - Data normalization
 */
final readonly class PrepareService implements PrepareServiceInterface
{
    public function __construct(
        private LoggerInterface $logger
    )
    {
    }

    /**
     * Prepare data by applying configured transformations.
     */
    public function prepare(PrepareConfigurationData $config): PrepareResultData
    {
        $prepareUsing = config('import-pipelines.prepare.using');

        if (!$prepareUsing) {
            return new PrepareResultData(
                preparedData: $config->data,
                originalData: $config->data,
                totalRows: count($config->data),
                preparedRows: count($config->data),
                skippedRows: 0,
                transformationStats: ['count' => 0],
                errors: [],
            );
        }

        // Accept either a ready ResolverInterface instance or a class-string
        // resolved through the container (consistent with import-pipelines.save.using).
        $resolver = is_string($prepareUsing) ? app($prepareUsing) : $prepareUsing;

        if (!($resolver instanceof ResolverInterface)) {
            throw new InvalidArgumentException(
                'import-pipelines.prepare.using must be a '.ResolverInterface::class.' instance or class-string.'
            );
        }

        $preparedData = [];
        $errors = [];
        $count = 0;


        foreach ($config->data as $index => $row) {
            try {
                $preparedData[] = $resolver->handle($row, $config);
                $count++;
            } catch (\Throwable $e) {
                $errors[(string)$index] = "Row {$index}: {$e->getMessage()}";

                $this->logger->warning('Row preparation failed', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                    'data' => $row,
                ]);
            }
        }

        return new PrepareResultData(
            preparedData: $preparedData,
            originalData: $config->data,
            totalRows: count($config->data),
            preparedRows: count($preparedData),
            skippedRows: count($errors),
            transformationStats: ['count' => $count],
            errors: $errors,
        );
    }
}
