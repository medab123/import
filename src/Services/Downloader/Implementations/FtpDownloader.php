<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Downloader\Implementations;

use Elaitech\Import\Services\Core\DTOs\DownloadRequestData;
use Elaitech\Import\Services\Core\DTOs\DownloadResultData;
use Elaitech\Import\Services\Core\DTOs\OptionDefinition;
use Elaitech\Import\Services\Core\Exceptions\DownloaderException;
use Elaitech\Import\Services\Downloader\Abstracts\AbstractDownloader;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Psr\Log\LoggerInterface;

final class FtpDownloader extends AbstractDownloader
{
    public function __construct(private readonly LoggerInterface $logger) {}

    protected function doDownload(DownloadRequestData $request, array $options): DownloadResultData
    {
        // Get file path from options
        $path = ltrim($options['file'] ?? '', '/');

        try {
            $disk = $this->buildFtpDisk($options);

            if (! $disk->exists($path)) {
                throw DownloaderException::fileNotFound('FTP', $path);
            }

            $contents = (string) $disk->get($path);
            $filename = $request->preferredFilename ?? basename($path);

            $this->logger->info('FTP download completed', [
                'host' => $options['host'] ?? null,
                'path' => $path,
                'filename' => $filename,
                'size' => strlen($contents),
            ]);

            return new DownloadResultData(
                success: true,
                fileSize: (string) strlen($contents),
                filename: $filename,
                mimeType: 'application/octet-stream',
                contents: $contents,
            );
        } catch (\Exception $e) {
            throw DownloaderException::downloadFailed('FTP', $e->getMessage());
        }
    }

    public function getOptionDefinitions(): array
    {
        return [
            'host' => new OptionDefinition(
                type: 'string',
                default: null,
                description: 'Host name (if not in URL)'
            ),
            'username' => new OptionDefinition(
                type: 'string',
                default: null,
                description: 'FTP username (if not in URL)'
            ),
            'password' => new OptionDefinition(
                type: 'string',
                default: null,
                description: 'FTP password (if not in URL)'
            ),
            'file' => new OptionDefinition(
                type: 'string',
                default: null,
                description: 'File path'
            ),
            'port' => new OptionDefinition(
                type: 'integer',
                default: 21,
                description: 'FTP port (if not in URL)',
                minValue: 1,
                maxValue: 65535
            ),
            'timeout' => new OptionDefinition(
                type: 'integer',
                default: 30,
                description: 'Connection timeout in seconds',
                minValue: 1,
                maxValue: 300
            ),
        ];
    }

    private function buildFtpDisk(array $options): Filesystem
    {
        $config = [
            'driver' => 'ftp',
            'host' => $options['host'] ?? '',
            'username' => $options['username'] ?? '',
            'password' => $options['password'] ?? '',
            'port' => $options['port'] ?? 21,
            'timeout' => $options['timeout'] ?? 30,
        ];

        return Storage::build($config);
    }
}
