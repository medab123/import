<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\Services;

use Elaitech\Import\Enums\ImportPipelineFrequency;
use Elaitech\Import\Enums\PipelineStatus;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Services\Pipeline\Contracts\PipelineSchedulingServiceInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

final class PipelineSchedulingService implements PipelineSchedulingServiceInterface
{
    public function isReadyForExecution($pipeline): bool
    {
        if ($pipeline->status !== PipelineStatus::ACTIVE || ! $pipeline->start_time) {
            return false;
        }

        $now = now();
        $currentTime = $now->format('H:i');
        $startTime = $pipeline->start_time->format('H:i');

        if (! $this->isTimeMatch($currentTime, $startTime)) {
            return false;
        }

        return match ($pipeline->frequency) {
            ImportPipelineFrequency::DAILY => $this->shouldExecuteDaily($pipeline, $now),
            ImportPipelineFrequency::WEEKLY => $this->shouldExecuteWeekly($pipeline, $now),
            ImportPipelineFrequency::MONTHLY => $this->shouldExecuteMonthly($pipeline, $now),
            default => false,
        };
    }

    public function calculateNextExecution($pipeline): ?Carbon
    {
        if ($pipeline->frequency === ImportPipelineFrequency::ONCE) {
            return null;
        }

        if (! $pipeline->start_time) {
            return null;
        }

        $now = now();
        $startTime = $pipeline->start_time->format('H:i');

        return match ($pipeline->frequency) {
            ImportPipelineFrequency::DAILY => $this->calculateDailyNextExecution($now, $startTime),
            ImportPipelineFrequency::WEEKLY => $this->calculateWeeklyNextExecution($pipeline, $now, $startTime),
            ImportPipelineFrequency::MONTHLY => $this->calculateMonthlyNextExecution($pipeline, $now, $startTime),
            default => null,
        };
    }

    public function getScheduledPipelines(?ImportPipelineFrequency $frequency = null): Collection
    {
        $query = ImportPipeline::query()
            ->active()
            ->scheduled()
            ->with(['company', 'config']);

        if ($frequency) {
            $query->byFrequency($frequency);
        }

        return $query->get();
    }

    public function shouldExecute($pipeline, Carbon $now): bool
    {
        if ($pipeline->status !== PipelineStatus::ACTIVE || ! $pipeline->start_time) {
            return false;
        }

        $currentTime = $now->format('H:i');
        $startTime = $pipeline->start_time->format('H:i');

        if (! $this->isTimeMatch($currentTime, $startTime)) {
            return false;
        }

        return match ($pipeline->frequency) {
            ImportPipelineFrequency::DAILY => $this->shouldExecuteDaily($pipeline, $now),
            ImportPipelineFrequency::WEEKLY => $this->shouldExecuteWeekly($pipeline, $now),
            ImportPipelineFrequency::MONTHLY => $this->shouldExecuteMonthly($pipeline, $now),
            default => false,
        };
    }

    private function isTimeMatch(string $currentTime, string $startTime): bool
    {
        $current = Carbon::createFromFormat('H:i', $currentTime);
        $start = Carbon::createFromFormat('H:i', $startTime);

        $tolerance = 1;

        $diffInHoures = abs($current->diffInHours($start));

        return $diffInHoures <= $tolerance;
    }

    private function shouldExecuteWeekly($pipeline, Carbon $now): bool
    {
        $createdDay = $pipeline->created_at->dayOfWeek;

        return $now->dayOfWeek === $createdDay;
    }

    private function shouldExecuteMonthly($pipeline, Carbon $now): bool
    {
        $createdDay = $pipeline->created_at->day;

        return $now->day === $createdDay;
    }

    private function shouldExecuteDaily(ImportPipeline $pipeline, Carbon $now): bool
    {
        $lastExecuted = $pipeline->last_executed_at;

        // Has it run today?
        if ($lastExecuted && $lastExecuted->isSameDay($now)) {
            return false;
        }

        return true;
    }

    private function calculateDailyNextExecution(Carbon $now, string $startTime): Carbon
    {
        $nextExecution = $now->copy()->setTimeFromTimeString($startTime);
        if ($nextExecution->lte($now)) {
            $nextExecution->addDay();
        }

        return $nextExecution;
    }

    private function calculateWeeklyNextExecution($pipeline, Carbon $now, string $startTime): Carbon
    {
        $createdDay = $pipeline->created_at->dayOfWeek;

        return $now->copy()->next($createdDay)->setTimeFromTimeString($startTime);
    }

    private function calculateMonthlyNextExecution($pipeline, Carbon $now, string $startTime): Carbon
    {
        $createdDay = $pipeline->created_at->day;
        $nextExecution = $now->copy()->day($createdDay)->setTimeFromTimeString($startTime);

        if ($nextExecution->lte($now)) {
            $nextExecution->addMonth();
        }

        return $nextExecution;
    }

    public function updateNextExecutionTime(ImportPipeline $pipeline): void
    {
        if (! $pipeline->isScheduled()) {
            $pipeline->update(['next_execution_at' => null]);

            return;
        }

        $nextExecution = $this->calculateNextExecution($pipeline);

        if ($nextExecution) {
            $pipeline->update(['next_execution_at' => $nextExecution]);
        }
    }

    public function updateAllNextExecutionTimes(): void
    {
        $pipelines = ImportPipeline::active()->scheduled()->get();

        foreach ($pipelines as $pipeline) {
            $this->updateNextExecutionTime($pipeline);
        }
    }
}
