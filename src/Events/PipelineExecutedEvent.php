<?php

declare(strict_types=1);

namespace Elaitech\Import\Events;

use Elaitech\Import\Models\ImportPipeline;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PipelineExecutedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ImportPipeline $pipeline,
        public readonly \DateTimeInterface $executedAt,
        public readonly string $triggeredBy
    ) {}
}
