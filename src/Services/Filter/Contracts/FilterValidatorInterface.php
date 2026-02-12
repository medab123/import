<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Filter\Contracts;

interface FilterValidatorInterface
{
    /**
     * Validate a single filter rule.
     *
     * @param  array<string, mixed>  $rule
     * @return array<string> Array of validation errors
     */
    public function validateRule(array $rule): array;

    /**
     * Validate multiple filter rules.
     *
     * @param  array<array<string, mixed>>  $rules
     * @return array<string, array<string>> Array of validation errors by rule index
     */
    public function validateRules(array $rules): array;

    /**
     * Check if a rule is valid.
     *
     * @param  array<string, mixed>  $rule
     */
    public function isValid(array $rule): bool;
}
