<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Filter\Extractors;

use Elaitech\Import\Services\Filter\Contracts\ValueExtractorInterface;

final class DotNotationValueExtractor implements ValueExtractorInterface
{
    public function extract(array $row, string $key): mixed
    {
        if (! str_contains($key, '.')) {
            return $row[$key] ?? null;
        }

        $keys = explode('.', $key);
        $value = $row;

        foreach ($keys as $k) {
            if (! is_array($value) || ! array_key_exists($k, $value)) {
                return null;
            }

            $value = $value[$k];
        }

        return $value;
    }

    public function exists(array $row, string $key): bool
    {
        if (! str_contains($key, '.')) {
            return array_key_exists($key, $row);
        }

        $keys = explode('.', $key);
        $value = $row;

        foreach ($keys as $k) {
            if (! is_array($value) || ! array_key_exists($k, $value)) {
                return false;
            }

            $value = $value[$k];
        }

        return true;
    }
}
