<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Reader\Implementations;

use Elaitech\Import\Services\Core\DTOs\OptionDefinition;
use Elaitech\Import\Services\Core\Exceptions\ReaderException;
use Elaitech\Import\Services\Reader\Abstracts\AbstractReader;

final class CsvReader extends AbstractReader
{
    protected function doRead(string $contents, array $options): array
    {
        $contents = $this->normalizeLineEndings($contents);
        // Config/YAML supplies tab as the two-character literal "\t"; fgetcsv
        // needs a single real tab character.
        $delimiter = $options['delimiter'] === '\t' ? "\t" : $options['delimiter'];
        $enclosure = $options['enclosure'];
        $escape = $options['escape'];
        $hasHeader = $options['has_header'];
        $trim = $options['trim'];
        $maxRows = isset($options['max_rows']) ? (int) $options['max_rows'] : null;

        $rows = [];
        $headers = [];

        $handle = fopen('php://temp', 'rb+');
        if ($handle === false) {
            throw ReaderException::parsingFailed('CSV', 'Unable to open temporary stream');
        }
        fwrite($handle, $contents);
        rewind($handle);

        while (($data = fgetcsv($handle, 0, $delimiter, $enclosure, $escape)) !== false) {
            if ($trim) {
                $data = array_map(static fn ($v) => is_string($v) ? trim($v) : $v, $data);
            }

            if ($hasHeader && empty($headers)) {
                $headers = $data;

                continue;
            }

            if ($hasHeader) {
                $rows[] = array_combine($headers, array_pad($data, count($headers), null));
            } else {
                $rows[] = $data;
            }

            // Stop early when a row cap is set (e.g. wizard column discovery).
            if ($maxRows !== null && count($rows) >= $maxRows) {
                break;
            }
        }

        fclose($handle);

        return $rows;
    }

    public function getOptionDefinitions(): array
    {
        return [
            'delimiter' => new OptionDefinition(
                type: 'string',
                default: ',',
                description: 'Field delimiter character',
                allowedValues: [',', ';', '\t', "\t", '|']
            ),
            'enclosure' => new OptionDefinition(
                type: 'string',
                default: '"',
                description: 'Field enclosure character',
                allowedValues: ['"', "'"]
            ),
            'escape' => new OptionDefinition(
                type: 'string',
                default: '\\',
                description: 'Escape character'
            ),
            'has_header' => new OptionDefinition(
                type: 'boolean',
                default: true,
                description: 'Whether first row contains headers'
            ),
            'trim' => new OptionDefinition(
                type: 'boolean',
                default: true,
                description: 'Trim whitespace from values'
            ),
        ];
    }
}
