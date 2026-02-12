<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Core\Operators;

interface FilterOperatorInterface
{
    /**
     * Get the operator name.
     */
    public function getName(): string;

    /**
     * Get the operator label for UI.
     */
    public function getLabel(): string;

    /**
     * Get the operator description.
     */
    public function getDescription(): string;

    /**
     * Check if this operator supports the given value type.
     */
    public function supportsValueType(mixed $value): bool;

    /**
     * Apply the filter operation.
     */
    public function apply(mixed $dataValue, mixed $filterValue, array $options = []): bool;

    /**
     * Get validation rules for this operator.
     */
    public function getValidationRules(): array;

    /**
     * Get the expected value type for this operator.
     */
    public function getExpectedValueType(): string;
}
