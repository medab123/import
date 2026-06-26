<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Downloader\Abstracts;

use Elaitech\Import\Services\Core\Contracts\DownloaderInterface;
use Elaitech\Import\Services\Core\DTOs\DownloadRequestData;
use Elaitech\Import\Services\Core\DTOs\DownloadResultData;
use Elaitech\Import\Services\Core\DTOs\OptionDefinition;
use Elaitech\Import\Services\Core\Exceptions\DownloaderException;
use Elaitech\Import\Services\Core\Traits\HasOptions;
use Elaitech\Import\Services\Core\Traits\ServiceTrait;
use Illuminate\Support\Str;

abstract class AbstractDownloader implements DownloaderInterface
{
    use HasOptions, ServiceTrait;

    /**
     * Reusable option definition for the OOM guard. Concrete downloaders should
     * include it in getOptionDefinitions() (key 'max_bytes', 0 = unlimited).
     */
    protected function maxBytesOption(): OptionDefinition
    {
        return new OptionDefinition(
            type: 'integer',
            default: 0,
            description: 'Maximum download size in bytes (0 = unlimited)',
            minValue: 0,
        );
    }

    /**
     * Guard against buffering an unexpectedly huge payload into memory.
     */
    protected function enforceMaxBytes(string $protocol, string $contents, array $options): void
    {
        $maxBytes = (int) ($options['max_bytes'] ?? 0);

        if ($maxBytes > 0 && strlen($contents) > $maxBytes) {
            throw DownloaderException::downloadFailed(
                $protocol,
                "Downloaded file exceeds the configured max_bytes limit ({$maxBytes} bytes)."
            );
        }
    }

    public function download(DownloadRequestData $request): DownloadResultData
    {
        $this->validateOptions($request->options);
        $options = $this->mergeWithDefaults($request->options);

        return $this->doDownload($request, $options);
    }

    /**
     * Perform the actual download logic. Implemented by concrete classes.
     *
     * @param  DownloadRequestData  $request  The download request
     * @param  array<string, mixed>  $options  Validated and merged options
     */
    abstract protected function doDownload(DownloadRequestData $request, array $options): DownloadResultData;

    protected function guessFilenameFromHeaders(?string $contentDisposition): ?string
    {
        if (! $contentDisposition) {
            return null;
        }

        if (preg_match("~filename\\*=UTF-8''([^;]+)|filename=\"?([^\"];+)\"?~i", $contentDisposition, $matches)) {
            $filename = $matches[1] ?? $matches[2] ?? null;

            return $filename ? Str::of($filename)->trim()->basename()->toString() : null;
        }

        return null;
    }
}
