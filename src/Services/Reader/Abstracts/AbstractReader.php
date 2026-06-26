<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Reader\Abstracts;

use Elaitech\Import\Services\Core\Contracts\ReaderInterface;
use Elaitech\Import\Services\Core\Traits\HasOptions;
use Elaitech\Import\Services\Core\Traits\ServiceTrait;

abstract class AbstractReader implements ReaderInterface
{
    use HasOptions, ServiceTrait;

    public function read(string $contents, array $options = []): array
    {
        // `max_rows` is a reserved cross-reader option (not part of any reader's
        // own definitions): cap the rows returned. Readers that can stop early
        // (e.g. CSV) also receive it via $options to avoid parsing the whole file.
        $maxRows = isset($options['max_rows']) && $options['max_rows'] !== null
            ? max(0, (int) $options['max_rows'])
            : null;
        unset($options['max_rows']);

        $this->validateOptions($options);
        $options = $this->mergeWithDefaults($options);

        if ($maxRows !== null) {
            $options['max_rows'] = $maxRows;
        }

        $rows = $this->doRead($contents, $options);

        if ($maxRows !== null && count($rows) > $maxRows) {
            $rows = array_slice($rows, 0, $maxRows);
        }

        return $rows;
    }

    /**
     * Perform the actual reading logic. Implemented by concrete classes.
     *
     * @param  string  $contents  Raw file contents
     * @param  array<string, mixed>  $options  Validated and merged options
     * @return array<mixed>
     */
    abstract protected function doRead(string $contents, array $options): array;

    protected function normalizeLineEndings(string $contents): string
    {
        return preg_replace("~\r\n?|\n~", "\n", $contents) ?? $contents;
    }

    /**
     * Convert scalar values to string and trim if requested.
     */
    protected function maybeTrim(mixed $value, bool $trim): mixed
    {
        if ($trim && is_string($value)) {
            return trim($value);
        }

        return $value;
    }
}
