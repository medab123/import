<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Core\Traits;

use Elaitech\Import\Services\Core\DTOs\OptionDefinition;

trait HasOptions
{
    /**
     * @return array<string, OptionDefinition>
     */
    abstract public function getOptionDefinitions(): array;

    /**
     * @return array<string, array{type: string, default: mixed, description: string}>
     */
    public function getOptions(): array
    {
        $definitions = $this->getOptionDefinitions();
        $options = [];

        foreach ($definitions as $key => $definition) {
            $options[$key] = [
                'type' => $definition->type,
                'default' => $definition->default,
                'description' => $definition->description,
            ];
        }

        return $options;
    }

    /**
     * @param  array<string, mixed>  $options
     *
     * @throws \InvalidArgumentException
     */
    public function validateOptions(array $options): void
    {
        $definitions = $this->getOptionDefinitions();

        // Only validate options that are defined for this downloader
        // Ignore other options that might be present in the unified options array
        foreach ($options as $key => $value) {
            if (isset($definitions[$key])) {
                $definition = $definitions[$key];
                $definition->validate($value);
            }
            // Ignore undefined options - they might be for other downloader types
        }
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function mergeWithDefaults(array $options): array
    {
        $definitions = $this->getOptionDefinitions();
        $merged = [];

        foreach ($definitions as $key => $definition) {
            $merged[$key] = $options[$key] ?? $definition->default;
        }

        return $merged;
    }
}
