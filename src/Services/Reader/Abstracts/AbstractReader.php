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
        $this->validateOptions($options);
        $options = $this->mergeWithDefaults($options);

        return $this->doRead($contents, $options);
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
