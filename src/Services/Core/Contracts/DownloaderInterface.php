<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Core\Contracts;

use Elaitech\Import\Services\Core\DTOs\DownloadRequestData;
use Elaitech\Import\Services\Core\DTOs\DownloadResultData;
use Elaitech\Import\Services\Core\DTOs\OptionDefinition;

interface DownloaderInterface extends ServiceInterface
{
    public function download(DownloadRequestData $request): DownloadResultData;

    /**
     * Get available options for this downloader.
     *
     * @return array<string, array{type: string, default: mixed, description: string}>
     */
    public function getOptions(): array;

    /**
     * Get option definitions for this downloader.
     *
     * @return array<string, OptionDefinition>
     */
    public function getOptionDefinitions(): array;

    /**
     * Validate options for this downloader.
     *
     * @param  array<string, mixed>  $options
     *
     * @throws \InvalidArgumentException
     */
    public function validateOptions(array $options): void;
}
