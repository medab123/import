<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Filter\Contracts;

use Elaitech\Import\Services\Core\DTOs\FilterConfigurationData;
use Elaitech\Import\Services\Core\DTOs\FilterResultData;

interface FilterInterface
{
    /**
     * Apply filters to the provided data.
     */
    public function filter(FilterConfigurationData $config): FilterResultData;

    /**
     * Get available filter operators.
     */
    public function getAvailableOperators(): array;

    /**
     * Validate a filter rule.
     */
    public function validateRule(array $rule): array;
}
