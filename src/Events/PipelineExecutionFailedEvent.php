<?php

declare(strict_types=1);

namespace Elaitech\Import\Events;

use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Models\ImportPipelineExecution;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class PipelineExecutionFailedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ImportPipeline $pipeline,
        public readonly ImportPipelineExecution $execution,
        public readonly Throwable $exception,
        public readonly \DateTimeInterface $failedAt
    ) {}
}
