<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Reader\Implementations;

use Elaitech\Import\Services\Core\DTOs\OptionDefinition;
use Elaitech\Import\Services\Core\Exceptions\ReaderException;
use Elaitech\Import\Services\Reader\Abstracts\AbstractReader;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class YamlReader extends AbstractReader
{
    protected function doRead(string $contents, array $options): array
    {
        try {
            // Uses symfony/yaml (a package dependency) — no PECL yaml extension required.
            $data = Yaml::parse($contents);
        } catch (ParseException $e) {
            throw ReaderException::parsingFailed('YAML', $e->getMessage());
        }

        if ($data === null || is_scalar($data) || ! is_array($data)) {
            return [];
        }

        // A YAML sequence is already a list of rows; a single map becomes one row.
        return array_is_list($data) ? $data : [$data];
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
