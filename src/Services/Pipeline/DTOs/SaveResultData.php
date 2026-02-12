<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\DTOs;

use App\Models\Product;
use Spatie\LaravelData\Data;

final class SaveResultData extends Data
{
    /**
     * @param  array<int, Product>  $createdProducts
     * @param  array<int, Product>  $updatedProducts
     * @param  array<string, string>  $errors  Array of row index => error message
     */
    public function __construct(
        public array $createdProducts = [],
        public array $updatedProducts = [],
        public array $errors = [],
        public int $totalProcessed = 0,
        public int $createdCount = 0,
        public int $updatedCount = 0,
        public int $errorCount = 0,
    ) {}

    public function getTotalSaved(): int
    {
        return $this->createdCount + $this->updatedCount;
    }

    public function getSuccessRate(): float
    {
        if ($this->totalProcessed === 0) {
            return 0.0;
        }

        return ($this->getTotalSaved() / $this->totalProcessed) * 100;
    }

    public function hasErrors(): bool
    {
        return $this->errorCount > 0;
    }

    public function getAllProductIds(): array
    {
        $ids = [];

        foreach ($this->createdProducts as $product) {
            $ids[] = $product->id;
        }

        foreach ($this->updatedProducts as $product) {
            $ids[] = $product->id;
        }

        return $ids;
    }
}
