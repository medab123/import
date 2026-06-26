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

        // Compare as strings so a CSV "5" matches a config list value 5.
        $needle = $this->convertToString($dataValue);
        $haystack = array_map(fn ($v) => $this->convertToString($v), $filterValue);

        if (! $caseSensitive) {
            $needle = strtolower($needle);
            $haystack = array_map('strtolower', $haystack);
        }

        return in_array($needle, $haystack, true);
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
