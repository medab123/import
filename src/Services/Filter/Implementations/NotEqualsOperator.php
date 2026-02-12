<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Filter\Implementations;

use Elaitech\Import\Services\Filter\Abstracts\AbstractFilterOperator;

final class NotEqualsOperator extends AbstractFilterOperator
{
    public function getName(): string
    {
        return 'not_equals';
    }

    public function getLabel(): string
    {
        return 'Not Equals';
    }

    public function getDescription(): string
    {
        return 'Check if the value does not match the filter value';
    }

    public function supportsValueType(mixed $value): bool
    {
        return true; // Supports all value types
    }

    protected function doApply(mixed $dataValue, mixed $filterValue, array $options): bool
    {
        $caseSensitive = $this->isCaseSensitive($options);

        if (is_string($dataValue) && is_string($filterValue)) {
            if (! $caseSensitive) {
                return strtolower($dataValue) !== strtolower($filterValue);
            }

            return $dataValue !== $filterValue;
        }

        return $dataValue !== $filterValue;
    }

    public function getExpectedValueType(): string
    {
        return 'mixed';
    }
}
