<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\DTOs;

use Elaitech\Import\Enums\ImageDownloadMode;
use Spatie\LaravelData\Data;

/**
 * Images Prepare Configuration Data
 *
 * Configuration for the images prepare stage, defining:
 * - Image separator character(s)
 * - Image indexes to skip
 * - Active status (whether to download images)
 * - Download mode (all, new products only, products without images)
 */
final class ImagesPrepareConfigurationData extends Data
{
    /**
     * @param  array<int, array<string, mixed>>  $data  The mapped data to process
     * @param  string  $imageSeparator  The separator used to split image URLs (default: ',')
     * @param  array<int>  $imageIndexesToSkip  Array of image indexes to skip (0-based)
     * @param  string  $imagesKey  The key name for images in the product data (default: 'images')
     * @param  bool  $active  Whether image download is active (default: false)
     * @param  ImageDownloadMode  $downloadMode  The download mode (default: ALL)
     */
    public function __construct(
        public array $data,
        public string $imageSeparator = ',',
        public array $imageIndexesToSkip = [],
        public string $imagesKey = 'images',
        public bool $active = false,
        public ImageDownloadMode $downloadMode = ImageDownloadMode::ALL,
    ) {}
}
