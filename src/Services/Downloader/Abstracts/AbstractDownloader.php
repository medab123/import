<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Downloader\Abstracts;

use Elaitech\Import\Services\Core\Contracts\DownloaderInterface;
use Elaitech\Import\Services\Core\DTOs\DownloadRequestData;
use Elaitech\Import\Services\Core\DTOs\DownloadResultData;
use Elaitech\Import\Services\Core\Traits\HasOptions;
use Elaitech\Import\Services\Core\Traits\ServiceTrait;
use Illuminate\Support\Str;

abstract class AbstractDownloader implements DownloaderInterface
{
    use HasOptions, ServiceTrait;

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
