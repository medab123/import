<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\Factories;

use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Models\ImportPipelineConfig as ImportPipelineConfigModel;
use Elaitech\DataMapper\DTO\MappingConfigurationData;
use Elaitech\DataMapper\DTO\MappingRuleData;
use Elaitech\Import\Services\Core\DTOs\DownloadRequestData;
use Elaitech\Import\Services\Core\DTOs\FilterConfigurationData;
use Elaitech\Import\Services\Core\DTOs\FilterRuleData;
use Elaitech\Import\Services\Pipeline\DTOs\ImportPipelineConfig;
use Elaitech\Import\Services\Pipeline\DTOs\ImportPipelineOptions;
use Elaitech\Import\Services\Pipeline\DTOs\ReaderConfig;

final class ImportPipelineConfigFactory
{
    /**
     * Create ImportPipelineConfig from database model
     */
    public function fromModel(ImportPipelineConfigModel $model): ImportPipelineConfig
    {
        return $this->fromArray($model->config_data);
    }

    /**
     * Create ImportPipelineConfig from pipeline model
     */
    public function fromPipeline(ImportPipeline $pipeline): ImportPipelineConfig
    {
        return ImportPipelineConfig::fromModel($pipeline);
    }

    /**
     * Create ImportPipelineConfig from array data
     */
    public function fromArray(array $configData): ImportPipelineConfig
    {
        return ImportPipelineConfig::from([
            'downloadRequest' => $this->buildDownloadRequest($configData['download'] ?? []),
            'readerConfig' => $this->buildReaderConfig($configData['read'] ?? []),
            'mappingConfig' => $this->buildMappingConfig($configData['map'] ?? []),
            'filterConfig' => $this->buildFilterConfig($configData['filter'] ?? []),
            'options' => $this->buildOptions($configData['options'] ?? []),
        ]);
    }

    /**
     * Create configuration for CSV import
     */
    public function createCsvImport(string $url, array $mappingRules = [], array $filterRules = []): ImportPipelineConfig
    {
        return $this->fromArray([
            'download' => [
                'type' => 'https',
                'url' => $url,
                'timeout' => 30,
            ],
            'read' => [
                'type' => 'csv',
                'options' => [
                    'delimiter' => ',',
                    'enclosure' => '"',
                    'escape' => '\\',
                    'has_header' => true,
                ],
            ],
            'map' => ['rules' => $mappingRules],
            'filter' => ['rules' => $filterRules],
            'options' => [
                'enable_caching' => true,
                'enable_logging' => true,
            ],
        ]);
    }

    // Helper methods for building DTOs
    private function buildDownloadRequest(array $config): DownloadRequestData
    {
        return new DownloadRequestData(
            source: $config['options']['source'] ?? '',
            options: array_merge(
                $config['options'] ?? [],
                [
                    'type' => $config['type'] ?? 'https',
                    'timeout' => $config['timeout'] ?? 30,
                ]
            )
        );
    }

    private function buildReaderConfig(array $config): ReaderConfig
    {
        return new ReaderConfig(
            type: $config['reader_type'],
            options: $config['options']
        );
    }

    private function buildMappingConfig(array $config): MappingConfigurationData
    {
        $rules = $config['field_mappings'] ?? [];

        $mappingRules = collect($rules)->map(function (array $rule) {
            return new MappingRuleData(
                sourceField: $rule['source_field'] ?? '',
                targetField: $rule['target_field'] ?? '',
                transformation: $rule['transformation'] ?? 'none',
                isRequired: $rule['is_required'] ?? false,
                defaultValue: $rule['default_value'] ?? null,
                format: $rule['format'] ?? null,
                valueMapping: $rule['value_mapping'] ?? null
            );
        });

        return new MappingConfigurationData(
            data: [],
            mappingRules: $mappingRules->toArray(),
            headers: []
        );
    }

    private function buildFilterConfig(array $config): ?FilterConfigurationData
    {
        $rules = $config['rules'] ?? [];

        if (empty($rules)) {
            return null;
        }

        $filterRules = collect($rules)->map(function (array $rule) {
            return new FilterRuleData(
                key: $rule['field'] ?? '',
                operator: $rule['operator'] ?? 'equals',
                value: $rule['value'] ?? null,
                description: $rule['description'] ?? null,
                caseSensitive: $rule['case_sensitive'] ?? false,
                regexFlags: $rule['regex_flags'] ?? null
            );
        });

        return new FilterConfigurationData(
            data: [],
            filterRules: $filterRules->toArray(),
        );
    }

    private function buildOptions(array $config): ImportPipelineOptions
    {
        return new ImportPipelineOptions(
            enableCaching: $config['enable_caching'] ?? true,
            enableLogging: $config['enable_logging'] ?? true,
            stopOnError: $config['stop_on_error'] ?? false,
            cacheKey: $config['cache_key'] ?? null,
            timeout: $config['timeout'] ?? 300,
            enableMetrics: $config['enable_metrics'] ?? true
        );
    }
}
