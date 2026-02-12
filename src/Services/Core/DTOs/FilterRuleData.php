<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Core\DTOs;

use Spatie\LaravelData\Data;

final class FilterRuleData extends Data
{
    public function __construct(
        public string $key,
        public string $operator,
        public mixed $value,
        public ?string $description = null,
        public bool $caseSensitive = false,
        public ?string $regexFlags = null, // For regex operations: 'i' for case insensitive, 'm' for multiline, etc.
    ) {
        $this->validateOperator();
    }

    private function validateOperator(): void
    {
        $validOperators = [
            'equals', 'not_equals', 'contains', 'not_contains',
            'starts_with', 'ends_with', 'regex', 'not_regex',
            'greater_than', 'less_than', 'greater_than_or_equal', 'less_than_or_equal',
            'in', 'not_in', 'is_null', 'is_not_null',
            'is_empty', 'is_not_empty', 'between', 'not_between',
        ];

        if (! in_array($this->operator, $validOperators, true)) {
            throw new \InvalidArgumentException(
                "Invalid operator '{$this->operator}'. Valid operators: ".implode(', ', $validOperators)
            );
        }
    }

    public function getDescription(): string
    {
        $value = is_array($this->value) ? implode(',', $this->value) : $this->value;

        return $this->description ?? "Filter by {$this->key} {$this->operator} {$value}";
    }

    public function isRegexOperator(): bool
    {
        return in_array($this->operator, ['regex', 'not_regex'], true);
    }

    public function isNumericOperator(): bool
    {
        return in_array($this->operator, [
            'greater_than', 'less_than', 'greater_than_or_equal',
            'less_than_or_equal', 'between', 'not_between',
        ], true);
    }

    public function isArrayOperator(): bool
    {
        return in_array($this->operator, ['in', 'not_in'], true);
    }

    public function isNullOperator(): bool
    {
        return in_array($this->operator, ['is_null', 'is_not_null'], true);
    }

    public function isStringOperator(): bool
    {
        return in_array($this->operator, [
            'contains', 'not_contains', 'starts_with', 'ends_with',
        ], true);
    }
}
