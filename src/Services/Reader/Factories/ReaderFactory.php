<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Reader\Factories;

use Elaitech\Import\Services\Core\Abstracts\BaseFactory;
use Elaitech\Import\Services\Core\Contracts\ReaderInterface;
use Elaitech\Import\Services\Reader\Contracts\ReaderFactoryInterface;

final class ReaderFactory extends BaseFactory implements ReaderFactoryInterface
{
    protected function validateServiceClass(string $className): void
    {
        if (! is_subclass_of($className, ReaderInterface::class)) {
            throw \Elaitech\Import\Services\Core\Exceptions\FactoryException::invalidServiceClass(
                $className,
                ReaderInterface::class
            );
        }
    }

    protected function getExpectedInterface(): string
    {
        return ReaderInterface::class;
    }

    public function for(string $type): ReaderInterface
    {
        return parent::for($type);
    }
}
