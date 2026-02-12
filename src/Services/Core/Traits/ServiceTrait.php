<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Core\Traits;

trait ServiceTrait
{
    public function getType(): string
    {
        return static::class;
    }
}
