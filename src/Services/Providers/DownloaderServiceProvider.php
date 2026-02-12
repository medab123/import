<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Providers;

use Elaitech\Import\Services\Downloader\Contracts\DownloaderFactoryInterface;
use Elaitech\Import\Services\Downloader\Factories\DownloaderFactory;
use Elaitech\Import\Services\Downloader\Implementations\FtpDownloader;
use Elaitech\Import\Services\Downloader\Implementations\HttpDownloader;
use Elaitech\Import\Services\Downloader\Implementations\SftpDownloader;
use Illuminate\Http\Client\Factory as HttpClient;
use Psr\Log\LoggerInterface;

final class DownloaderServiceProvider extends BaseImportServiceProvider
{
    protected function getServiceMappings(): array
    {

        return config('import-pipelines.downloaders', []);
    }

    protected function getFactoryClass(): string
    {
        return DownloaderFactory::class;
    }

    protected function getFactoryInterface(): string
    {
        return DownloaderFactoryInterface::class;
    }

    protected function getServiceType(): string
    {
        return 'downloader';
    }
}
