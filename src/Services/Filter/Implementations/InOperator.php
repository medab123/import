<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Filter\Implementations;

use Elaitech\Import\Services\Filter\Abstracts\AbstractFilterOperator;

final class InOperator extends AbstractFilterOperator
{
    public function getName(): string
    {
        return 'in';
    }

    public function getLabel(): string
    {
        return 'In List';
    }

    public function getDescription(): string
    {
        return 'Check if the value is in the provided list';
    }

    public function supportsValueType(mixed $value): bool
    {
        return true; // Supports all value types
    }

    protected function doApply(mixed $dataValue, mixed $filterValue, array $options): bool
    {
        if (! $this->isArrayValue($filterValue)) {
            return false;
        }

        $caseSensitive = $this->isCaseSensitive($options);

        if (is_string($dataValue) && ! $caseSensitive) {
            $dataValue = strtolower($dataValue);
            $filterValue = array_map('strtolower', $filterValue);
        }

        return in_array($dataValue, $filterValue, true);
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
