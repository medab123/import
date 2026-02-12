<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Filter\Implementations;

use Elaitech\Import\Services\Filter\Abstracts\AbstractFilterOperator;

final class GreaterThanOperator extends AbstractFilterOperator
{
    public function getName(): string
    {
        return 'greater_than';
    }

    public function getLabel(): string
    {
        return 'Greater Than';
    }

    public function getDescription(): string
    {
        return 'Check if the value is greater than the filter value';
    }

    public function supportsValueType(mixed $value): bool
    {
        return $this->isNumericValue($value);
    }

    protected function doApply(mixed $dataValue, mixed $filterValue, array $options): bool
    {
        $dataNumeric = $this->convertToNumeric($dataValue);
        $filterNumeric = $this->convertToNumeric($filterValue);

        if ($dataNumeric === null || $filterNumeric === null) {
            return false;
        }

        return $dataNumeric > $filterNumeric;
    }

    public function getExpectedValueType(): string
    {
        return 'numeric';
    }
}
