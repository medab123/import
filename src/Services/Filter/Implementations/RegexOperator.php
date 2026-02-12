<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Filter\Implementations;

use Elaitech\Import\Services\Filter\Abstracts\AbstractFilterOperator;

final class RegexOperator extends AbstractFilterOperator
{
    public function getName(): string
    {
        return 'regex';
    }

    public function getLabel(): string
    {
        return 'Regex Match';
    }

    public function getDescription(): string
    {
        return 'Check if the value matches the regular expression pattern';
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
            return false;
        }

        $flags = $this->getRegexFlags($options);
        $pattern = $this->buildRegexPattern($filterValue, $flags);

        try {
            $result = preg_match($pattern, $dataValue);

            return $result === 1;
        } catch (\Exception $e) {
            // Invalid regex pattern
            return false;
        }
    }

    private function buildRegexPattern(string $pattern, string $flags): string
    {
        // Add delimiters if not present
        if (! preg_match('/^[\/~#%].*[\/~#%][imsxADSUXJu]*$/', $pattern)) {
            $pattern = '/'.$pattern.'/';
        }

        // Add flags
        if (! empty($flags)) {
            $pattern .= $flags;
        }

        return $pattern;
    }

    public function getValidationRules(): array
    {
        return [
            'value' => 'required|string|regex:/^[^\/]*$/', // Basic validation
        ];
    }

    public function getExpectedValueType(): string
    {
        return 'string';
    }
}
