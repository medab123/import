<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Filter\Implementations;

use Elaitech\Import\Services\Filter\Abstracts\AbstractFilterOperator;

final class NotInOperator extends AbstractFilterOperator
{
    public function getName(): string
    {
        return 'not_in';
    }

    public function getLabel(): string
    {
        return 'Not In List';
    }

    public function getDescription(): string
    {
        return 'Check if the value is not in the provided list';
    }

    public function supportsValueType(mixed $value): bool
    {
        return true; // Supports all value types
    }

    protected function doApply(mixed $dataValue, mixed $filterValue, array $options): bool
    {
        if (! $this->isArrayValue($filterValue)) {
            return true; // Not in is true when filter value is not an array
        }

        $caseSensitive = $this->isCaseSensitive($options);

        if (is_string($dataValue) && ! $caseSensitive) {
            $dataValue = strtolower($dataValue);
            $filterValue = array_map('strtolower', $filterValue);
        }

        return ! in_array($dataValue, $filterValue, true);
    }

    public function getValidationRules(): array
    {
        return [
            'value' => 'required|array|min:1',
        ];
    }

    public function getExpectedValueType(): string
    {
        return 'array';
    }
}
