<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\DTOs;

use Elaitech\Import\Enums\ImageDownloadMode;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\DataMapper\DTO\MappingConfigurationData;
use Elaitech\DataMapper\DTO\MappingRuleData;
use Elaitech\Import\Services\Core\DTOs\DownloadRequestData;
use Elaitech\Import\Services\Core\DTOs\FilterConfigurationData;
use Elaitech\Import\Services\Core\DTOs\FilterRuleData;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class ImportPipelineConfig extends Data
{
    public function __construct(
        public DownloadRequestData $downloadRequest,
        public ReaderConfig $readerConfig,
        public ?MappingConfigurationData $mappingConfig,
        public ?FilterConfigurationData $filterConfig = null,
        public ?ImagesPrepareConfigurationData $imagesPrepareConfig = null,
        public ?PrepareConfigurationData $prepareConfig = null,
        public ImportPipelineOptions $options = new ImportPipelineOptions,
        public ?int $targetId = null
    ) {}

    /**
     * Create ImportPipelineConfig from ImportPipeline model.
     *
     * @param  ImportPipeline  $pipeline  The pipeline model
     * @param  bool  $requireMapper  Whether mapper config is required (default: true)
     */
    public static function fromModel(ImportPipeline $pipeline, bool $requireMapper = true): self
    {
        $downloaderConfig = $pipeline->getDownloaderConfig();
        $readerConfig = $pipeline->getReaderConfig();
        $mapperConfig = $pipeline->getMapperConfig();
        $filterConfig = $pipeline->getFilterConfig();
        $imagesPrepareConfig = $pipeline->getImagesPrepareConfig();

        if (! $downloaderConfig) {
            throw new \InvalidArgumentException('Pipeline does not have a downloader configuration');
        }

        if (! $readerConfig) {
            throw new \InvalidArgumentException('Pipeline does not have a reader configuration');
        }

        if ($requireMapper && ! $mapperConfig) {
            throw new \InvalidArgumentException('Pipeline does not have a mapper configuration');
        }

        return new self(
            downloadRequest: self::buildDownloadRequest($downloaderConfig->config_data),
            readerConfig: self::buildReaderConfig($readerConfig->config_data),
            mappingConfig: $mapperConfig ? self::buildMappingConfig($mapperConfig->config_data) : null,
            filterConfig: $filterConfig ? self::buildFilterConfig($filterConfig->config_data) : null,
            imagesPrepareConfig: $imagesPrepareConfig ? self::buildImagesPrepareConfig($imagesPrepareConfig->config_data) : null,
            prepareConfig: self::buildPrepareConfig($pipeline),
            options: new ImportPipelineOptions,
            targetId: (int)$pipeline->target_id
        );
    }

    /**
     * Build DownloadRequestData from config data.
     */
    private static function buildDownloadRequest(array $config): DownloadRequestData
    {
        $downloaderType = $config['downloader_type'] ?? 'https';
        $options = $config['options'] ?? [];

        return new DownloadRequestData(
            source: $options['source'] ?? '',
            options: array_merge($options, [
                'type' => $downloaderType,
                'timeout' => $config['timeout'] ?? 30,
            ])
        );
    }

    /**
     * Build ReaderConfig from config data.
     */
    private static function buildReaderConfig(array $config): ReaderConfig
    {
        return new ReaderConfig(
            type: $config['reader_type'] ?? 'csv',
            options: $config['options'] ?? []
        );
    }

    /**
     * Build MappingConfigurationData from config data.
     */
    private static function buildMappingConfig(array $config): MappingConfigurationData
    {
        $rules = $config['field_mappings'] ?? [];

        return new MappingConfigurationData(
            data: [],
            mappingRules: new DataCollection(
                MappingRuleData::class,
                $rules
            ),
            headers: $config['headers'] ?? []
        );
    }

    /**
     * Build FilterConfigurationData from config data.
     */
    private static function buildFilterConfig(array $config): ?FilterConfigurationData
    {
        $rules = $config['rules'] ?? [];

        if (empty($rules)) {
            return new FilterConfigurationData(
                data: [],
                filterRules: [],
            );
        }

        $filterRules = array_map(function (array $rule) {
            return new FilterRuleData(
                key: $rule['field'] ?? $rule['key'] ?? '',
                operator: $rule['operator'] ?? 'equals',
                value: $rule['value'] ?? null,
                description: $rule['description'] ?? null,
                caseSensitive: $rule['case_sensitive'] ?? false,
                regexFlags: $rule['regex_flags'] ?? null
            );
        }, $rules);

        return new FilterConfigurationData(
            data: [],
            filterRules: $filterRules,
        );
    }

    /**
     * Build ImagesPrepareConfigurationData from config data.
     */
    private static function buildImagesPrepareConfig(array $config): ImagesPrepareConfigurationData
    {
        return new ImagesPrepareConfigurationData(
            data: [],
            imageSeparator: $config['image_separator'] ?? ',',
            imageIndexesToSkip: $config['image_indexes_to_skip'] ?? [],
            imagesKey: $config['images_key'] ?? 'images',
            active: $config['active'] ?? false,
            downloadMode: ImageDownloadMode::from($config['download_mode'] ?? 'all'),
        );
    }

    /**
     * Build PrepareConfigurationData from pipeline model.
     * Uses default transformations from config file.
     */
    private static function buildPrepareConfig(ImportPipeline $pipeline): ?PrepareConfigurationData
    {
        $resolvers = config('import-pipelines.resolvers', []);
        $transformationNames = array_keys($resolvers);

        return new PrepareConfigurationData(
            data: [],
            targetId: (int)$pipeline->target_id,
            transformations: $transformationNames,
        );
    }
}
