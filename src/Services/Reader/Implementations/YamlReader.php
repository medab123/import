<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Reader\Implementations;

use Elaitech\Import\Services\Core\DTOs\OptionDefinition;
use Elaitech\Import\Services\Core\Exceptions\ReaderException;
use Elaitech\Import\Services\Reader\Abstracts\AbstractReader;

final class YamlReader extends AbstractReader
{
    protected function doRead(string $contents, array $options): array
    {
        try {
            // Note: yaml_parse requires yaml extension
            if (! function_exists('yaml_parse')) {
                throw new \Exception('YAML extension not available');
            }

            $data = yaml_parse($contents);
            if ($data === false) {
                throw new \Exception('Failed to parse YAML');
            }

            return $data;
        } catch (\Exception $e) {
            throw ReaderException::parsingFailed('YAML', $e->getMessage());
        }
    }

    public function getOptionDefinitions(): array
    {
        return [
            'encoding' => new OptionDefinition(
                type: 'string',
                default: 'UTF-8',
                description: 'Character encoding for the YAML file'
            ),
        ];
    }

    public function getType(): string
    {
        return 'yaml';
    }
}
