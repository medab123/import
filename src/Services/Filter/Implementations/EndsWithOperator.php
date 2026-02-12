<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Filter\Implementations;

use Elaitech\Import\Services\Filter\Abstracts\AbstractFilterOperator;

final class EndsWithOperator extends AbstractFilterOperator
{
    public function getName(): string
    {
        return 'ends_with';
    }

    public function getLabel(): string
    {
        return 'Ends With';
    }

    public function getDescription(): string
    {
        return 'Check if the value ends with the filter value';
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
            return strripos($dataValue, $filterValue) === (strlen($dataValue) - strlen($filterValue));
        }

        return strrpos($dataValue, $filterValue) === (strlen($dataValue) - strlen($filterValue));
    }

    public function getExpectedValueType(): string
    {
        return 'string';
    }
}
