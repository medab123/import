<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\Contracts;

use Elaitech\Import\Enums\ImportPipelineFrequency;
use Elaitech\Import\Models\ImportPipeline;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

interface PipelineSchedulingServiceInterface
{
    public function isReadyForExecution(ImportPipeline $pipeline): bool;

    public function calculateNextExecution(ImportPipeline $pipeline): ?Carbon;

    public function getScheduledPipelines(?ImportPipelineFrequency $frequency = null): Collection;

    public function shouldExecute(ImportPipeline $pipeline, Carbon $now): bool;

    public function updateNextExecutionTime(ImportPipeline $pipeline): void;

    public function updateAllNextExecutionTimes(): void;
}
