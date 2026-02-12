<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Core\DTOs;

final readonly class OptionDefinition
{
    public function __construct(
        public string $type,
        public mixed $default,
        public string $description,
        public bool $required = false,
        public ?array $allowedValues = null,
        public ?int $minValue = null,
        public ?int $maxValue = null,
    ) {}

    public function validate(mixed $value): void
    {
        if ($this->required && $value === null) {
            throw new \InvalidArgumentException('Option is required');
        }

        if ($value === null && ! $this->required) {
            return;
        }

        $this->validateType($value);
        $this->validateConstraints($value);
    }

    private function validateType(mixed $value): void
    {
        $isValid = match ($this->type) {
            'string' => is_string($value),
            'integer' => is_int($value),
            'boolean' => is_bool($value),
            'array' => is_array($value),
            'float' => is_float($value) || is_int($value),
            default => true,
        };

        if (! $isValid) {
            throw new \InvalidArgumentException("Expected {$this->type}, got ".gettype($value));
        }
    }

    private function validateConstraints(mixed $value): void
    {
        if ($this->allowedValues !== null && ! in_array($value, $this->allowedValues, true)) {
            throw new \InvalidArgumentException('Value not in allowed values: '.implode(', ', $this->allowedValues));
        }

        if (is_numeric($value)) {
            $numericValue = (float) $value;
            if ($this->minValue !== null && $numericValue < $this->minValue) {
                throw new \InvalidArgumentException("Value must be >= {$this->minValue}");
            }
            if ($this->maxValue !== null && $numericValue > $this->maxValue) {
                throw new \InvalidArgumentException("Value must be <= {$this->maxValue}");
            }
        }
    }
}
