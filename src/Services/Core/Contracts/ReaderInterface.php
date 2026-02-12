<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Core\Contracts;

interface ReaderInterface extends ServiceInterface
{
    /**
     * @param  string  $contents  Raw file contents
     * @param  array<string, mixed>  $options  Reader-specific options (e.g., delimiter for CSV)
     * @return array<mixed>
     */
    public function read(string $contents, array $options = []): array;

    /**
     * Get available options for this reader.
     *
     * @return array<string, array{type: string, default: mixed, description: string}>
     */
    public function getOptions(): array;

    /**
     * Validate options for this reader.
     *
     * @param  array<string, mixed>  $options
     *
     * @throws \InvalidArgumentException
     */
    public function validateOptions(array $options): void;
}
