<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Filter\Contracts;

interface ValueExtractorInterface
{
    /**
     * Extract a value from a data row using dot notation.
     */
    public function extract(array $row, string $key): mixed;

    /**
     * Check if a key exists in the data row.
     */
    public function exists(array $row, string $key): bool;
}
