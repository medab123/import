<?php

namespace Elaitech\Import\Enums;

enum ImportPipelineStep: string
{
    case BasicInfo = 'basic-info';
    case DownloaderConfig = 'downloader-config';
    case ReaderConfig = 'reader-config';
    case FilterConfig = 'filter-config';
    case MapperConfig = 'mapper-config';
    case ImagesPrepareConfig = 'images-prepare-config';
    case PrepareConfig = 'prepare-config';
    case Preview = 'preview';

    public function title(): string
    {
        return match ($this) {
            self::BasicInfo => 'Basic Information',
            self::DownloaderConfig => 'Downloader Configuration',
            self::ReaderConfig => 'Reader Configuration',
            self::FilterConfig => 'Filter Configuration',
            self::MapperConfig => 'Mapper Configuration',
            self::ImagesPrepareConfig => 'Images Prepare Configuration',
            self::PrepareConfig => 'Prepare Configuration',
            self::Preview => 'Preview',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::BasicInfo => 'Configure basic pipeline information and settings',
            self::DownloaderConfig => 'Set up data downloader configuration',
            self::ReaderConfig => 'Configure data reader and parsing settings',
            self::FilterConfig => 'Define data filtering rules and conditions',
            self::MapperConfig => 'Set up data mapping and transformation rules',
            self::ImagesPrepareConfig => 'Configure image preparation settings (image indexes to skip, image separator, etc.)',
            self::PrepareConfig => 'Configure data preparation and transformation rules (category ID resolution, VIN/Stock ID generation, etc.)',
            self::Preview => 'See the pipeline Output data ',
        };
    }

    public function route(): string
    {
        return match ($this) {
            self::BasicInfo, self::DownloaderConfig, self::ReaderConfig,
            self::FilterConfig, self::MapperConfig, self::ImagesPrepareConfig, self::PrepareConfig, self::Preview => 'dashboard.import.pipelines.step.show',
        };
    }

    public function order(): int
    {
        return match ($this) {
            self::BasicInfo => 1,
            self::DownloaderConfig => 2,
            self::ReaderConfig => 3,
            self::FilterConfig => 4,
            self::MapperConfig => 5,
            self::ImagesPrepareConfig => 6,
            self::PrepareConfig => 7,
            self::Preview => 8,
        };
    }
}
