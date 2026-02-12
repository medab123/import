<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Reader\Implementations;

use Elaitech\Import\Services\Core\DTOs\OptionDefinition;
use Elaitech\Import\Services\Core\Exceptions\ReaderException;
use Elaitech\Import\Services\Reader\Abstracts\AbstractReader;

final class JsonReader extends AbstractReader
{
    protected function doRead(string $contents, array $options): array
    {
        try {
            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw ReaderException::parsingFailed('JSON', $e->getMessage());
        }

        // Apply entry point if specified
        if (! empty($options['entry_point'])) {
            $data = $this->extractDataByEntryPoint($data, $options['entry_point']);
        }

        // Ensure we always return an array
        if (! is_array($data)) {
            return [$data];
        }

        // If the result is an associative array (not a list), wrap it in an array
        if (! array_is_list($data)) {
            return [$data];
        }

        return $data;
    }

    public function getOptionDefinitions(): array
    {
        return [
            'entry_point' => new OptionDefinition(
                type: 'string',
                default: '',
                description: 'Dot notation path to extract data from (e.g., "inventory.listing")'
            ),
        ];
    }

    /**
     * Extract data using dot notation entry point.
     */
    private function extractDataByEntryPoint(array $data, string $entryPoint): mixed
    {
        $keys = explode('.', $entryPoint);
        $current = $data;

        foreach ($keys as $key) {
            if (! is_array($current) || ! array_key_exists($key, $current)) {
                throw ReaderException::parsingFailed(
                    'JSON',
                    "Entry point '{$entryPoint}' not found in JSON data. Key '{$key}' does not exist."
                );
            }
            $current = $current[$key];
        }

        return $current;
    }
}
