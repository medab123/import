<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Reader\Contracts;

use Elaitech\Import\Services\Core\Contracts\FactoryInterface;
use Elaitech\Import\Services\Core\Contracts\ReaderInterface;

interface ReaderFactoryInterface extends FactoryInterface
{
    public function for(string $type): ReaderInterface;
}
