<?php

namespace Elaitech\Import\Services\Core\Contracts;

use Illuminate\Database\Eloquent\Model;

interface DownloadStrategyInterface
{
    /**
     * Download images for the given host model (expected to expose the host's
     * media API, e.g. spatie/laravel-medialibrary). Host-agnostic by type.
     */
    public function download(array $urls, Model $product): array;
}
