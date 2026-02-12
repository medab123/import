<?php

namespace Elaitech\Import\Services\Core\Contracts;

use App\Models\Product;

interface DownloadStrategyInterface
{
    /**
     * Download images in parallel.
     */
    public function download(array $urls, Product $product): array;
}
