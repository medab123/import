<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Filter\Implementations;

use Elaitech\Import\Services\Filter\Abstracts\AbstractFilterOperator;

final class IsNotNullOperator extends AbstractFilterOperator
{
    public function getName(): string
    {
        return 'is_not_null';
    }

    public function getLabel(): string
    {
        return 'Is Not Null';
    }

    public function getDescription(): string
    {
        return 'Check if the value is not null or empty';
    }

    public function supportsValueType(mixed $value): bool
    {
        return true; // Supports all value types
    }

    protected function doApply(mixed $dataValue, mixed $filterValue, array $options): bool
    {
        return ! $this->isNullValue($dataValue);
    }

    public function getExpectedValueType(): string
    {
        return 'mixed';
    }
}
