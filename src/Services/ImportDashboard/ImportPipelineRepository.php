<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\ImportDashboard;

use Elaitech\Import\Contracts\Repositories\ImportPipeline\ImportPipelineRepositoryInterface;
use Elaitech\Import\Enums\ImportPipelineFrequency;
use Elaitech\Import\Enums\ImportPipelineStatus;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Models\ImportPipelineExecution;
use Elaitech\Import\Models\ImportPipelineTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

final readonly class ImportPipelineRepository implements ImportPipelineRepositoryInterface
{
    public function __construct(
        private ImportPipeline $pipeline,
        private ImportPipelineExecution $execution,
        private ImportPipelineTemplate $template
    ) {}

    // Pipeline CRUD Operations
    public function create(array $data): ImportPipeline
    {
        return $this->pipeline->create($data);
    }

    public function findById(int $id): ?ImportPipeline
    {
        return $this->pipeline->with(['config', 'creator', 'updater'])->find($id);
    }

    public function update(int $id, array $data): bool
    {
        return (bool) $this->pipeline->where('id', $id)->update($data);
    }

    public function delete(int $id): int
    {
        return $this->pipeline->where('id', $id)->delete();
    }

    // Pipeline Queries
    public function getByTargetId(int $targetId): Collection
    {
        return $this->pipeline
            ->with(['config', 'creator'])
            ->where('target_id', $targetId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getActiveByTargetId(int $targetId): Collection
    {
        return $this->pipeline
            ->with(['config', 'creator'])
            ->where('target_id', $targetId)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getScheduledPipelines(): Collection
    {
        return $this->pipeline
            ->with(['config'])
            ->where('is_active', true)
            ->where('frequency', '!=', ImportPipelineFrequency::ONCE)
            ->orderBy('start_time')
            ->get();
    }

    public function getByFrequency(ImportPipelineFrequency $frequency): Collection
    {
        return $this->pipeline
            ->with(['config', 'creator'])
            ->where('frequency', $frequency)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function paginate(?int $targetId = null, int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        $builder = $this->pipeline
            ->with(['config', 'creator']);

        if ($targetId) {
            $builder->where('target_id', $targetId);
        }

        if ($search !== null && trim($search) !== '') {
            $searchTerm = trim($search);
            $builder->where(function ($q) use ($searchTerm) {
                $q->where('name', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('description', 'ILIKE', "%{$searchTerm}%")
                    ->orWhereHas('creator', function ($creatorQuery) use ($searchTerm) {
                        $creatorQuery->where('name', 'ILIKE', "%{$searchTerm}%");
                    });
            });
        }

        return $builder->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function search(string $query, ?int $targetId = null): Collection
    {
        $builder = $this->pipeline
            ->with(['config', 'creator'])
            ->where(function ($q) use ($query) {
                $q->where('name', 'ILIKE', "%{$query}%")
                    ->orWhere('description', 'ILIKE', "%{$query}%");
            });

        if ($targetId) {
            $builder->where('target_id', $targetId);
        }

        return $builder->orderBy('created_at', 'desc')->get();
    }

    // Execution Queries
    public function getExecutionsByPipeline(int $pipelineId, int $limit = 10): Collection
    {
        return $this->execution
            ->with(['pipeline', 'logs'])
            ->where('pipeline_id', $pipelineId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function paginateExecutionsByPipeline(int $pipelineId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->execution
            ->with(['pipeline', 'logs'])
            ->where('pipeline_id', $pipelineId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function findExecutionById(int $id): ?ImportPipelineExecution
    {
        return $this->execution
            ->with(['pipeline', 'logs'])
            ->find($id);
    }

    public function getRecentExecutions(?int $targetId = null, int $limit = 20): Collection
    {
        $builder = $this->execution
            ->with(['pipeline', 'pipeline.config'])
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if ($targetId) {
            $builder->whereHas('pipeline', function ($q) use ($targetId) {
                $q->where('target_id', $targetId);
            });
        }

        return $builder->get();
    }

    public function getExecutionsByStatus(ImportPipelineStatus $status, ?int $targetId = null): Collection
    {
        $builder = $this->execution
            ->with(['pipeline', 'pipeline.config'])
            ->where('status', $status);

        if ($targetId) {
            $builder->whereHas('pipeline', function ($q) use ($targetId) {
                $q->where('target_id', $targetId);
            });
        }

        return $builder->orderBy('created_at', 'desc')->get();
    }

    public function getFailedExecutions(?int $targetId = null, int $limit = 10): Collection
    {
        return $this->getExecutionsByStatus(ImportPipelineStatus::FAILED, $targetId)
            ->take($limit);
    }

    public function getRunningExecutions(?int $targetId = null): Collection
    {
        return $this->getExecutionsByStatus(ImportPipelineStatus::RUNNING, $targetId);
    }

    // Statistics and Analytics
    public function getPipelineStats(?int $targetId = null): array
    {
        $builder = $this->pipeline->query();
        if ($targetId) {
            $builder->where('target_id', $targetId);
        }

        $totalPipelines = $builder->count();
        $activePipelines = $builder->where('is_active', true)->count();
        $scheduledPipelines = $builder->where('frequency', '!=', ImportPipelineFrequency::ONCE)->count();

        return [
            'total_pipelines' => $totalPipelines,
            'active_pipelines' => $activePipelines,
            'inactive_pipelines' => $totalPipelines - $activePipelines,
            'scheduled_pipelines' => $scheduledPipelines,
            'one_time_pipelines' => $totalPipelines - $scheduledPipelines,
        ];
    }

    public function getExecutionStats(?int $targetId = null, int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);

        $builder = $this->execution->query();
        if ($targetId) {
            $builder->whereHas('pipeline', function ($q) use ($targetId) {
                $q->where('target_id', $targetId);
            });
        }

        $totalExecutions = $builder->where('created_at', '>=', $startDate)->count();
        $completedExecutions = $builder->where('status', ImportPipelineStatus::COMPLETED)->count();
        $failedExecutions = $builder->where('status', ImportPipelineStatus::FAILED)->count();
        $runningExecutions = $builder->where('status', ImportPipelineStatus::RUNNING)->count();

        $successRate = $totalExecutions > 0 ? ($completedExecutions / $totalExecutions) * 100 : 0;

        return [
            'total_executions' => $totalExecutions,
            'completed_executions' => $completedExecutions,
            'failed_executions' => $failedExecutions,
            'running_executions' => $runningExecutions,
            'success_rate' => round($successRate, 2),
            'period_days' => $days,
        ];
    }

    public function getPerformanceStats(?int $targetId = null, int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);

        $builder = $this->execution->query();
        if ($targetId) {
            $builder->whereHas('pipeline', function ($q) use ($targetId) {
                $q->where('target_id', $targetId);
            });
        }

        $executions = $builder
            ->where('created_at', '>=', $startDate)
            ->where('status', ImportPipelineStatus::COMPLETED)
            ->get();

        if ($executions->isEmpty()) {
            return [
                'avg_processing_time' => 0,
                'avg_memory_usage' => 0,
                'total_rows_processed' => 0,
                'avg_success_rate' => 0,
            ];
        }

        $avgProcessingTime = $executions->avg('processing_time');
        $avgMemoryUsage = $executions->avg('memory_usage');
        $totalRowsProcessed = $executions->sum('processed_rows');
        $avgSuccessRate = $executions->avg('success_rate');

        return [
            'avg_processing_time' => round($avgProcessingTime, 3),
            'avg_memory_usage' => round($avgMemoryUsage, 2),
            'total_rows_processed' => $totalRowsProcessed,
            'avg_success_rate' => round($avgSuccessRate, 2),
        ];
    }

    // Template Operations
    public function getTemplates(bool $publicOnly = false): Collection
    {
        $builder = $this->template->with(['creator']);

        if ($publicOnly) {
            $builder->where('is_public', true);
        }

        return $builder->orderBy('created_at', 'desc')->get();
    }

    public function getTemplateById(int $id): ?ImportPipelineTemplate
    {
        return $this->template->with(['creator'])->find($id);
    }

    public function createTemplate(array $data): ImportPipelineTemplate
    {
        return $this->template->create($data);
    }

    // Dashboard Data
    public function getDashboardData(?int $targetId = null): array
    {
        return [
            'pipeline_stats' => $this->getPipelineStats($targetId),
            'execution_stats' => $this->getExecutionStats($targetId),
            'performance_stats' => $this->getPerformanceStats($targetId),
            'recent_executions' => $this->getRecentExecutions($targetId, 10),
            'failed_executions' => $this->getFailedExecutions($targetId, 5),
            'running_executions' => $this->getRunningExecutions($targetId),
            'scheduled_pipelines' => $this->getScheduledPipelines(),
        ];
    }
}
