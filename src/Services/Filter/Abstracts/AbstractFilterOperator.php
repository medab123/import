<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Filter\Abstracts;

use Elaitech\Import\Services\Core\Operators\FilterOperatorInterface;

abstract class AbstractFilterOperator implements FilterOperatorInterface
{
    /**
     * Normalize a value for consistent processing.
     */
    protected function normalizeValue(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);

            return $value === '' ? null : $value;
        }

        return $value;
    }

    /**
     * Check if a value is considered null/empty.
     */
    protected function isNullValue(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    /**
     * Check if a value is numeric.
     */
    protected function isNumericValue(mixed $value): bool
    {
        return is_numeric($value);
    }

    /**
     * Check if a value is a string.
     */
    protected function isStringValue(mixed $value): bool
    {
        return is_string($value);
    }

    /**
     * Check if a value is an array.
     */
    protected function isArrayValue(mixed $value): bool
    {
        return is_array($value);
    }

    /**
     * Convert a value to numeric, returning null if not possible.
     */
    protected function convertToNumeric(mixed $value): ?float
    {
        return $this->isNumericValue($value) ? (float) $value : null;
    }

    /**
     * Convert a value to string representation.
     */
    protected function convertToString(mixed $value): string
    {
        return match (true) {
            $value === null => '',
            is_array($value) => implode(', ', $value),
            default => (string) $value,
        };
    }

    /**
     * Get validation rules for this operator.
     */
    public function getValidationRules(): array
    {
        return [];
    }

    /**
     * Get the expected value type for this operator.
     */
    public function getExpectedValueType(): string
    {
        return 'mixed';
    }

    /**
     * Validate that the operator supports the given value type.
     */
    protected function validateValueType(mixed $value): void
    {
        if (! $this->supportsValueType($value)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Operator "%s" does not support value type "%s"',
                    $this->getName(),
                    gettype($value)
                )
            );
        }
    }

    /**
     * Get case sensitivity option from operator options.
     */
    protected function isCaseSensitive(array $options): bool
    {
        return $options['case_sensitive'] ?? false;
    }

    /**
     * Get regex flags from operator options.
     */
    protected function getRegexFlags(array $options): string
    {
        return $options['regex_flags'] ?? '';
    }

    /**
     * Common apply method that handles normalization and validation.
     * Concrete classes should implement doApply() instead of apply().
     */
    public function apply(mixed $dataValue, mixed $filterValue, array $options = []): bool
    {
        $dataValue = $this->normalizeValue($dataValue);
        $filterValue = $this->normalizeValue($filterValue);

        if ($this->isNullValue($dataValue) || $this->isNullValue($filterValue)) {
            return $this->handleNullValues($dataValue, $filterValue);
        }

        $this->validateValueType($dataValue);

        return $this->doApply($dataValue, $filterValue, $options);
    }

    /**
     * Handle null/empty values. Override in subclasses for custom behavior.
     */
    protected function handleNullValues(mixed $dataValue, mixed $filterValue): bool
    {
        return false;
    }

    /**
     * Implement the actual filtering logic in subclasses.
     */
    abstract protected function doApply(mixed $dataValue, mixed $filterValue, array $options): bool;
}
