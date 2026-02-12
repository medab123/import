<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\DTOs;

use Spatie\LaravelData\Data;

final class ReaderConfig extends Data
{
    public function __construct(
        public string $type,
        public array $options = []
    ) {}
}
