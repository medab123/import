<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Providers;

use Elaitech\Import\Services\Reader\Contracts\ReaderFactoryInterface;
use Elaitech\Import\Services\Reader\Factories\ReaderFactory;
use Elaitech\Import\Services\Reader\Implementations\CsvReader;
use Elaitech\Import\Services\Reader\Implementations\JsonReader;
use Elaitech\Import\Services\Reader\Implementations\XmlReader;

final class ReaderServiceProvider extends BaseImportServiceProvider
{
    protected function getServiceMappings(): array
    {
        return config('import-pipelines.readers', []);
    }

    protected function getFactoryClass(): string
    {
        return ReaderFactory::class;
    }

    protected function getFactoryInterface(): string
    {
        return ReaderFactoryInterface::class;
    }

    protected function getServiceType(): string
    {
        return 'reader';
    }
}
