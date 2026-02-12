<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Filter\Registry;

use Elaitech\Import\Services\Core\Exceptions\FilterException;
use Elaitech\Import\Services\Core\Operators\FilterOperatorInterface;
use Elaitech\Import\Services\Filter\Contracts\OperatorRegistryInterface;

final class OperatorRegistry implements OperatorRegistryInterface
{
    /** @var array<string, FilterOperatorInterface> */
    private array $operators = [];

    public function register(FilterOperatorInterface $operator): void
    {
        $this->operators[$operator->getName()] = $operator;
    }

    public function get(string $name): FilterOperatorInterface
    {
        if (! $this->has($name)) {
            throw FilterException::unknownOperator($name);
        }

        return $this->operators[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->operators[$name]);
    }

    /**
     * @return array<string, FilterOperatorInterface>
     */
    public function all(): array
    {
        return $this->operators;
    }

    public function getMetadata(): array
    {
        $metadata = [];

        foreach ($this->operators as $name => $operator) {
            $metadata[$name] = [
                'name' => $operator->getName(),
                'label' => $operator->getLabel(),
                'description' => $operator->getDescription(),
                'expected_value_type' => $operator->getExpectedValueType(),
                'validation_rules' => $operator->getValidationRules(),
            ];
        }

        return $metadata;
    }
}
