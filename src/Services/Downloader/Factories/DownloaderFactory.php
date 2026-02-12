<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Downloader\Factories;

use Elaitech\Import\Services\Core\Abstracts\BaseFactory;
use Elaitech\Import\Services\Core\Contracts\DownloaderInterface;
use Elaitech\Import\Services\Downloader\Contracts\DownloaderFactoryInterface;

final class DownloaderFactory extends BaseFactory implements DownloaderFactoryInterface
{
    protected function validateServiceClass(string $className): void
    {
        if (! is_subclass_of($className, DownloaderInterface::class)) {
            throw \Elaitech\Import\Services\Core\Exceptions\FactoryException::invalidServiceClass(
                $className,
                DownloaderInterface::class
            );
        }
    }

    protected function getExpectedInterface(): string
    {
        return DownloaderInterface::class;
    }

    public function for(string $scheme): DownloaderInterface
    {
        return parent::for($scheme);
    }

    public function getAvailableSchemes(): array
    {
        return $this->getAvailableTypes();
    }
}
