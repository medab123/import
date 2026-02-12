<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Filter\Validators;

use Elaitech\Import\Services\Core\Exceptions\FilterException;
use Elaitech\Import\Services\Filter\Contracts\FilterValidatorInterface;
use Elaitech\Import\Services\Filter\Contracts\OperatorRegistryInterface;

final class FilterValidator implements FilterValidatorInterface
{
    private const REQUIRED_FIELDS = ['key', 'operator', 'value'];

    public function __construct(
        private readonly OperatorRegistryInterface $operatorRegistry
    ) {}

    public function validateRule(array $rule): array
    {
        $errors = [];

        // Validate required fields
        foreach (self::REQUIRED_FIELDS as $field) {
            if (! array_key_exists($field, $rule) || $this->isEmpty($rule[$field])) {
                $errors[] = "Field '{$field}' is required";
            }
        }

        if (! empty($errors)) {
            return $errors;
        }

        // Validate operator
        if (! $this->operatorRegistry->has($rule['operator'])) {
            $errors[] = "Unknown operator: {$rule['operator']}";

            return $errors;
        }

        // Validate operator-specific rules
        try {
            $operator = $this->operatorRegistry->get($rule['operator']);
            $this->validateOperatorSpecificRules($operator, $rule, $errors);
        } catch (FilterException $e) {
            $errors[] = $e->getMessage();
        }

        return $errors;
    }

    public function validateRules(array $rules): array
    {
        $errors = [];

        foreach ($rules as $index => $rule) {
            $ruleErrors = $this->validateRule($rule);
            if (! empty($ruleErrors)) {
                $errors["rule_{$index}"] = $ruleErrors;
            }
        }

        return $errors;
    }

    public function isValid(array $rule): bool
    {
        return empty($this->validateRule($rule));
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && empty($value));
    }

    private function validateOperatorSpecificRules(
        \Elaitech\Import\Services\Core\Operators\FilterOperatorInterface $operator,
        array $rule,
        array &$errors
    ): void {
        $validationRules = $operator->getValidationRules();

        if (empty($validationRules)) {
            return;
        }

        foreach ($validationRules as $field => $ruleDefinition) {
            if (! array_key_exists($field, $rule)) {
                continue;
            }

            $value = $rule[$field];
            $this->validateFieldValue($field, $value, $ruleDefinition, $errors);
        }
    }

    private function validateFieldValue(string $field, mixed $value, mixed $ruleDefinition, array &$errors): void
    {
        if (is_string($ruleDefinition)) {
            $this->validateStringRule($field, $value, $ruleDefinition, $errors);
        } elseif (is_array($ruleDefinition)) {
            $this->validateArrayRule($field, $value, $ruleDefinition, $errors);
        }
    }

    private function validateStringRule(string $field, mixed $value, string $rule, array &$errors): void
    {
        match ($rule) {
            'required' => $this->validateRequired($field, $value, $errors),
            'string' => $this->validateString($field, $value, $errors),
            'array' => $this->validateArray($field, $value, $errors),
            'numeric' => $this->validateNumeric($field, $value, $errors),
            default => null,
        };
    }

    private function validateArrayRule(string $field, mixed $value, array $rules, array &$errors): void
    {
        foreach ($rules as $rule) {
            $this->validateStringRule($field, $value, $rule, $errors);
        }
    }

    private function validateRequired(string $field, mixed $value, array &$errors): void
    {
        if ($this->isEmpty($value)) {
            $errors[] = "Field '{$field}' is required";
        }
    }

    private function validateString(string $field, mixed $value, array &$errors): void
    {
        if (! is_string($value)) {
            $errors[] = "Field '{$field}' must be a string";
        }
    }

    private function validateArray(string $field, mixed $value, array &$errors): void
    {
        if (! is_array($value)) {
            $errors[] = "Field '{$field}' must be an array";
        }
    }

    private function validateNumeric(string $field, mixed $value, array &$errors): void
    {
        if (! is_numeric($value)) {
            $errors[] = "Field '{$field}' must be numeric";
        }
    }
}
