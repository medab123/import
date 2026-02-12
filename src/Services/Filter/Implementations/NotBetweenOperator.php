<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Filter\Implementations;

use Elaitech\Import\Services\Filter\Abstracts\AbstractFilterOperator;

final class NotBetweenOperator extends AbstractFilterOperator
{
    public function getName(): string
    {
        return 'not_between';
    }

    public function getLabel(): string
    {
        return 'Not Between';
    }

    public function getDescription(): string
    {
        return 'Check if the value is not between two values (exclusive)';
    }

    public function supportsValueType(mixed $value): bool
    {
        return true; // Supports all value types
    }

    protected function doApply(mixed $dataValue, mixed $filterValue, array $options): bool
    {
        if (! $this->isArrayValue($filterValue) || count($filterValue) !== 2) {
            return true; // Not between is true when filter value is invalid
        }

        [$minValue, $maxValue] = $filterValue;

        // Handle null values
        if ($this->isNullValue($dataValue) || $this->isNullValue($minValue) || $this->isNullValue($maxValue)) {
            return true; // Not between is true for null values
        }

        // For numeric values, compare numerically
        if ($this->isNumericValue($dataValue) && $this->isNumericValue($minValue) && $this->isNumericValue($maxValue)) {
            $dataNumeric = $this->convertToNumeric($dataValue);
            $minNumeric = $this->convertToNumeric($minValue);
            $maxNumeric = $this->convertToNumeric($maxValue);

            if ($dataNumeric === null || $minNumeric === null || $maxNumeric === null) {
                return true;
            }

            // Ensure min <= max
            if ($minNumeric > $maxNumeric) {
                [$minNumeric, $maxNumeric] = [$maxNumeric, $minNumeric];
            }

            return ! ($dataNumeric >= $minNumeric && $dataNumeric <= $maxNumeric);
        }

        // For string values, compare lexicographically
        if (is_string($dataValue) && is_string($minValue) && is_string($maxValue)) {
            $caseSensitive = $this->isCaseSensitive($options);

            if (! $caseSensitive) {
                $dataValue = strtolower($dataValue);
                $minValue = strtolower($minValue);
                $maxValue = strtolower($maxValue);
            }

            // Ensure min <= max lexicographically
            if (strcmp($minValue, $maxValue) > 0) {
                [$minValue, $maxValue] = [$maxValue, $minValue];
            }

            return ! (strcmp($dataValue, $minValue) >= 0 && strcmp($dataValue, $maxValue) <= 0);
        }

        // For mixed types, convert to string and compare
        $dataString = $this->convertToString($dataValue);
        $minString = $this->convertToString($minValue);
        $maxString = $this->convertToString($maxValue);

        // Ensure min <= max
        if (strcmp($minString, $maxString) > 0) {
            [$minString, $maxString] = [$maxString, $minString];
        }

        return ! (strcmp($dataString, $minString) >= 0 && strcmp($dataString, $maxString) <= 0);
    }

    public function getValidationRules(): array
    {
        return [
            'value' => 'required|array|size:2',
        ];
    }

    public function getExpectedValueType(): string
    {
        return 'array';
    }
}
