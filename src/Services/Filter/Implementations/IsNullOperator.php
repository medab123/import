<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Filter\Implementations;

use Elaitech\Import\Services\Filter\Abstracts\AbstractFilterOperator;

final class IsNullOperator extends AbstractFilterOperator
{
    public function getName(): string
    {
        return 'is_null';
    }

    public function getLabel(): string
    {
        return 'Is Null';
    }

    public function getDescription(): string
    {
        return 'Check if the value is null or empty';
    }

    public function supportsValueType(mixed $value): bool
    {
        return true; // Supports all value types
    }

    /**
     * This operator consumes no filter value, so it must bypass the abstract
     * apply()'s null-filter-value short-circuit and evaluate the data directly.
     */
    public function apply(mixed $dataValue, mixed $filterValue, array $options = []): bool
    {
        return $this->doApply($this->normalizeValue($dataValue), $filterValue, $options);
    }

    protected function doApply(mixed $dataValue, mixed $filterValue, array $options): bool
    {
        return $this->isNullValue($dataValue);
    }

    public function getExpectedValueType(): string
    {
        return 'mixed';
    }
}
