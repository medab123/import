<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Filter\Implementations;

use Elaitech\Import\Services\Filter\Abstracts\AbstractFilterOperator;

final class StartsWithOperator extends AbstractFilterOperator
{
    public function getName(): string
    {
        return 'starts_with';
    }

    public function getLabel(): string
    {
        return 'Starts With';
    }

    public function getDescription(): string
    {
        return 'Check if the value starts with the filter value';
    }

    public function supportsValueType(mixed $value): bool
    {
        return $this->isStringValue($value);
    }

    protected function doApply(mixed $dataValue, mixed $filterValue, array $options): bool
    {
        if (! $this->isStringValue($dataValue)) {
            $dataValue = $this->convertToString($dataValue);
        }

        if (! $this->isStringValue($filterValue)) {
            $filterValue = $this->convertToString($filterValue);
        }

        $caseSensitive = $this->isCaseSensitive($options);

        if (! $caseSensitive) {
            return stripos($dataValue, $filterValue) === 0;
        }

        return strpos($dataValue, $filterValue) === 0;
    }

    public function getExpectedValueType(): string
    {
        return 'string';
    }
}
