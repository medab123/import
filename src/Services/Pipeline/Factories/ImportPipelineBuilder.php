<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\Factories;

use Elaitech\DataMapper\DTO\MappingConfigurationData;
use Elaitech\DataMapper\DTO\MappingRuleData;
use Elaitech\Import\Services\Core\DTOs\DownloadRequestData;
use Elaitech\Import\Services\Core\DTOs\FilterConfigurationData;
use Elaitech\Import\Services\Core\DTOs\FilterRuleData;
use Elaitech\Import\Services\Pipeline\DTOs\ImportPipelineConfig;
use Elaitech\Import\Services\Pipeline\DTOs\ImportPipelineOptions;
use Elaitech\Import\Services\Pipeline\DTOs\ReaderConfig;

final class ImportPipelineBuilder
{
    private ?DownloadRequestData $downloadRequest = null;

    private ?ReaderConfig $readerConfig = null;

    private ?MappingConfigurationData $mappingConfig = null;

    private ?FilterConfigurationData $filterConfig = null;

    private ImportPipelineOptions $options;

    public function __construct()
    {
        $this->options = new ImportPipelineOptions;
    }

    public function download(string $url, array $options = [], ?string $preferredFilename = null): self
    {
        $this->downloadRequest = new DownloadRequestData(
            source: $url,
            options: $options,
            preferredFilename: $preferredFilename
        );

        return $this;
    }

    public function reader(string $type, array $options = []): self
    {
        $this->readerConfig = new ReaderConfig(
            type: $type,
            options: $options
        );

        return $this;
    }

    public function map(array $mappingRules, array $headers = []): self
    {
        $this->mappingConfig = new MappingConfigurationData(
            data: [], // Will be set during execution
            mappingRules: $this->convertToMappingRules($mappingRules),
            headers: $headers
        );

        return $this;
    }

    public function filter(array $filterRules): self
    {
        $this->filterConfig = new FilterConfigurationData(
            data: [],
            filterRules: $this->convertToFilterRules($filterRules),
        );

        return $this;
    }

    public function withOptions(ImportPipelineOptions $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function enableCaching(bool $enable = true): self
    {
        $this->options = new ImportPipelineOptions(
            enableCaching: $enable,
            enableLogging: $this->options->enableLogging,
            stopOnError: $this->options->stopOnError,
            cacheKey: $this->options->cacheKey,
            timeout: $this->options->timeout,
            enableMetrics: $this->options->enableMetrics
        );

        return $this;
    }

    public function enableLogging(bool $enable = true): self
    {
        $this->options = new ImportPipelineOptions(
            enableCaching: $this->options->enableCaching,
            enableLogging: $enable,
            stopOnError: $this->options->stopOnError,
            cacheKey: $this->options->cacheKey,
            timeout: $this->options->timeout,
            enableMetrics: $this->options->enableMetrics
        );

        return $this;
    }

    public function stopOnError(bool $stop = true): self
    {
        $this->options = new ImportPipelineOptions(
            enableCaching: $this->options->enableCaching,
            enableLogging: $this->options->enableLogging,
            stopOnError: $stop,
            cacheKey: $this->options->cacheKey,
            timeout: $this->options->timeout,
            enableMetrics: $this->options->enableMetrics
        );

        return $this;
    }

    public function withCacheKey(string $cacheKey): self
    {
        $this->options = new ImportPipelineOptions(
            enableCaching: $this->options->enableCaching,
            enableLogging: $this->options->enableLogging,
            stopOnError: $this->options->stopOnError,
            cacheKey: $cacheKey,
            timeout: $this->options->timeout,
            enableMetrics: $this->options->enableMetrics
        );

        return $this;
    }

    public function withTimeout(int $timeout): self
    {
        $this->options = new ImportPipelineOptions(
            enableCaching: $this->options->enableCaching,
            enableLogging: $this->options->enableLogging,
            stopOnError: $this->options->stopOnError,
            cacheKey: $this->options->cacheKey,
            timeout: $timeout,
            enableMetrics: $this->options->enableMetrics
        );

        return $this;
    }

    public function build(): ImportPipelineConfig
    {
        if (! $this->downloadRequest) {
            throw new \InvalidArgumentException('Download configuration is required');
        }

        if (! $this->readerConfig) {
            throw new \InvalidArgumentException('Reader configuration is required');
        }

        if (! $this->mappingConfig) {
            throw new \InvalidArgumentException('Mapping configuration is required');
        }

        return ImportPipelineConfig::from([
            'downloadRequest' => $this->downloadRequest,
            'readerConfig' => $this->readerConfig,
            'mappingConfig' => $this->mappingConfig,
            'filterConfig' => $this->filterConfig,
            'options' => $this->options,
        ]);
    }

    private function convertToMappingRules(array $rules): array
    {
        $mappingRules = [];

        foreach ($rules as $rule) {
            if (is_array($rule)) {
                $mappingRules[] = new MappingRuleData(
                    sourceField: $rule['source_field'] ?? '',
                    targetField: $rule['target_field'] ?? '',
                    transformation: $rule['transformer'] ?? 'none',
                    isRequired: $rule['required'] ?? false,
                    defaultValue: $rule['default_value'] ?? null
                );
            } elseif ($rule instanceof MappingRuleData) {
                $mappingRules[] = $rule;
            }
        }

        return $mappingRules;
    }

    private function convertToFilterRules(array $rules): array
    {
        $filterRules = [];

        foreach ($rules as $rule) {
            if (is_array($rule)) {
                $filterRules[] = new FilterRuleData(
                    key: $rule['key'] ?? '',
                    operator: $rule['operator'] ?? '',
                    value: $rule['value'] ?? null,
                    caseSensitive: $rule['case_sensitive'] ?? false,
                    regexFlags: $rule['regex_flags'] ?? ''
                );
            } elseif ($rule instanceof FilterRuleData) {
                $filterRules[] = $rule;
            }
        }

        return $filterRules;
    }
}
