<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Core\Contracts;

interface ServiceInterface
{
    /**
     * Get the service type identifier.
     */
    public function getType(): string;

    /**
     * Get available options for this service.
     *
     * @return array<string, array{type: string, default: mixed, description: string}>
     */
    public function getOptions(): array;

    /**
     * Validate options for this service.
     *
     * @param  array<string, mixed>  $options
     *
     * @throws \InvalidArgumentException
     */
    public function validateOptions(array $options): void;
}
