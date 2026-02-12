<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Downloader\Contracts;

use Elaitech\Import\Services\Core\Contracts\DownloaderInterface;
use Elaitech\Import\Services\Core\Contracts\FactoryInterface;

interface DownloaderFactoryInterface extends FactoryInterface
{
    public function for(string $scheme): DownloaderInterface;
}
