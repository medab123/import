<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\ValueObjects;

use Elaitech\Import\Models\ImportPipeline;
use Carbon\Carbon;

final readonly class ExecutionContext
{
    public function __construct(
        public ImportPipeline $pipeline,
        public Carbon $executionTime,
        public ?string $triggeredBy = null,
        public array $metadata = []
    ) {}

    public function isScheduled(): bool
    {
        return $this->triggeredBy === 'scheduler';
    }

    public function isManual(): bool
    {
        return $this->triggeredBy === 'manual';
    }

    public function withMetadata(array $metadata): self
    {
        return new self(
            $this->pipeline,
            $this->executionTime,
            $this->triggeredBy,
            array_merge($this->metadata, $metadata)
        );
    }
}
