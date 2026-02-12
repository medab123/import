<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\Services;

use Elaitech\Import\Enums\PipelineStage;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Services\Pipeline\Contracts\ImportPipelineInterface;
use Elaitech\Import\Services\Pipeline\DTOs\ImportPipelineConfig;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

final class FeedKeysService
{
    private const string CACHE_PREFIX = 'import_pipeline_feed_keys_v2';

    private const int CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private readonly ImportPipelineInterface $pipelineService,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Get available feed field keys from a pipeline's reader step, with caching.
     *
     * @return array<int, array{key: string, preview: mixed, display: string, uniqueValues: array<int, mixed>}>
     */
    public function getFeedKeys(ImportPipeline $pipeline): array
    {
        return $this->fetchFeedKeysFromPipeline($pipeline);

        $cacheKey = $this->getCacheKey($pipeline->id);

        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $feedKeys = $this->fetchFeedKeysFromPipeline($pipeline);

        if ($feedKeys) {
            Cache::put($cacheKey, $feedKeys, self::CACHE_TTL);
        }

        return $feedKeys;
    }

    /**
     * Fetch feed keys directly from pipeline (no caching).
     *
     * @return array<int, array{key: string, preview: mixed, display: string, uniqueValues: array<int, mixed>}>
     */
    private function fetchFeedKeysFromPipeline(ImportPipeline $pipeline): array
    {
        try {
            $this->logger->info('Fetching feed keys from pipeline', [
                'pipeline_id' => $pipeline->id,
            ]);

            $pipelineConfig = ImportPipelineConfig::fromModel($pipeline, requireMapper: false);

            $result = $this->pipelineService->executeToStage($pipelineConfig, PipelineStage::READ);

            if ($result->readResult === null) {
                $this->logger->warning('No read result available for feed keys extraction', [
                    'pipeline_id' => $pipeline->id,
                ]);

                return [];
            }

            $feedKeys = $this->extractFieldKeys($result->readResult->data);

            $this->logger->info('Feed keys extracted successfully', [
                'pipeline_id' => $pipeline->id,
                'keys_count' => count($feedKeys),
                'sample_keys' => array_slice($feedKeys, 0, 5),
            ]);

            return $feedKeys;
        } catch (\Throwable $e) {

            $this->logger->error('Failed to fetch feed keys from pipeline', [
                'pipeline_id' => $pipeline->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    /**
     * Extract field keys from raw data and return flattened field paths with unique values.
     *
     * @param  array<mixed>  $data
     * @return array<int, array{key: string, preview: mixed, display: string, uniqueValues: array}>
     */
    private function extractFieldKeys(array $data): array
    {
        $rows = collect($data);

        if ($rows->isEmpty()) {
            return [];
        }

        $fieldValues = $this->collectFieldValues($rows);

        return $this->buildFieldKeyOutput($fieldValues);
    }

    /**
     * Flatten all rows into field paths and collect their values.
     *
     * @param  Collection<int, mixed>  $rows
     * @return Collection<string, array<int, mixed>>
     */
    private function collectFieldValues(Collection $rows): Collection
    {
        $fieldValues = [];

        foreach ($rows as $row) {
            $this->flattenRow($row, '', $fieldValues);
        }

        return collect($fieldValues)
            ->map(fn (array $values) => collect($values)
                ->unique()
                ->sortDesc()
                ->values()
                ->toArray()
            );
    }

    /**
     * Recursively flatten a row into field paths.
     *
     * @param  array<string, array<int, mixed>>  $result
     */
    private function flattenRow(mixed $data, string $prefix, array &$result): void
    {
        if (is_array($data)) {
            if (Arr::isAssoc($data)) {
                foreach ($data as $key => $value) {
                    $this->flattenRow($value, $this->appendKey($prefix, $key), $result);
                }
            } else {
                foreach ($data as $value) {
                    $this->flattenRow($value, $this->appendKey($prefix, '*'), $result);
                }
            }
        } else {
            $result[$prefix][] = $data;
        }
    }

    /**
     * Append child key to parent prefix.
     */
    private function appendKey(string $prefix, string $key): string
    {
        return $prefix === '' ? $key : $prefix.'.'.$key;
    }

    /**
     * Build the final output array with preview and display.
     *
     * @param  Collection<string, array<int, mixed>>  $fieldValues
     * @return array<int, array{key: string, preview: mixed, display: string, uniqueValues: array<int, mixed>}>
     */
    private function buildFieldKeyOutput(Collection $fieldValues): array
    {
        return $fieldValues
            ->map(fn (array $values, string $key) => $this->mapFieldOutput($key, $values))
            ->sortBy('key')
            ->values()
            ->toArray();
    }

    /**
     * Map single field key to structured output.
     *
     * @param  array<int, mixed>  $values
     * @return array{key: string, preview: mixed, display: string, uniqueValues: array<int, mixed>}
     */
    private function mapFieldOutput(string $key, array $values): array
    {
        // Truncate each value to 100 chars and fix UTF-8
        $truncatedValues = array_map(function ($value) {
            // Ensure it's a string
            $value = (string) $value;

            // Fix malformed UTF-8
            $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');

            // Truncate to 100 characters
            return mb_strlen($value) > 100 ? mb_substr($value, 0, 100) . '...' : $value;
        }, $values);

        // Generate preview from the first value
        $preview = $truncatedValues[0] ?? null;
        if ($preview && mb_strlen($preview) > 50) {
            $preview = mb_substr($preview, 0, 50) . '...';
        }

        return [
            'key' => $key,
            'preview' => $preview,
            'display' => $key . ' | ' . $preview,
            'uniqueValues' => $truncatedValues,
        ];
    }

    /**
     * Generate a cache key for a pipeline.
     */
    private function getCacheKey(int $pipelineId): string
    {
        return self::CACHE_PREFIX.':'.$pipelineId;
    }
}
