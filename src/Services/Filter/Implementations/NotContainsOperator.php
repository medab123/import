<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Filter\Implementations;

use Elaitech\Import\Services\Filter\Abstracts\AbstractFilterOperator;

final class NotContainsOperator extends AbstractFilterOperator
{
    public function getName(): string
    {
        return 'not_contains';
    }

    public function getLabel(): string
    {
        return 'Not Contains';
    }

    public function getDescription(): string
    {
        return 'Check if the value does not contain the filter value';
    }

    public function supportsValueType(mixed $value): bool
    {
        return $this->isStringValue($value) || $this->isArrayValue($value);
    }

    protected function doApply(mixed $dataValue, mixed $filterValue, array $options): bool
    {
        $caseSensitive = $this->isCaseSensitive($options);

        return match (true) {
            $this->isArrayValue($dataValue) => ! $this->applyToArray($dataValue, $filterValue, $caseSensitive),
            $this->isStringValue($dataValue) => ! $this->applyToString($dataValue, $filterValue, $caseSensitive),
            default => true, // Not contains is true for unsupported types
        };
    }

    private function applyToArray(array $dataValue, mixed $filterValue, bool $caseSensitive): bool
    {
        $filterValue = $this->convertToString($filterValue);

        foreach ($dataValue as $item) {
            $item = $this->convertToString($item);
            if ($this->stringContains($item, $filterValue, $caseSensitive)) {
                return true;
            }
        }

        return false;
    }

    private function applyToString(string $dataValue, mixed $filterValue, bool $caseSensitive): bool
    {
        $filterValue = $this->convertToString($filterValue);

        return $this->stringContains($dataValue, $filterValue, $caseSensitive);
    }

    private function stringContains(string $haystack, string $needle, bool $caseSensitive): bool
    {
        return $caseSensitive
            ? strpos($haystack, $needle) !== false
            : stripos($haystack, $needle) !== false;
    }

    public function getExpectedValueType(): string
    {
        return 'string|array';
    }
}
