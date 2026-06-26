<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\ValueObjects;

use Carbon\Carbon;

final readonly class TimeMatcher
{
    public function __construct(
        private int $toleranceMinutes = 5
    ) {}

    public function isTimeMatch(string $currentTime, string $startTime): bool
    {
        $current = Carbon::createFromFormat('H:i', $currentTime);
        $start = Carbon::createFromFormat('H:i', $startTime);

        return $current->diffInMinutes($start) <= $this->toleranceMinutes;
    }

    public function withTolerance(int $toleranceMinutes): self
    {
        return new self($toleranceMinutes);
    }
}
