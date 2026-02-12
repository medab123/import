<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Core\DTOs;

use Spatie\LaravelData\Data;

final class FilterConfigurationData extends Data
{
    public function __construct(
        public array $data,
        /** @var FilterRuleData[] */
        public array $filterRules,
    ) {}

    public function hasRules(): bool
    {
        return ! empty($this->filterRules);
    }

    public function getRuleCount(): int
    {
        return count($this->filterRules);
    }
}
