<?php

declare(strict_types=1);

namespace Elaitech\Import\Contracts\Repositories\ImportPipeline;

use Elaitech\Import\Enums\ImportPipelineFrequency;
use Elaitech\Import\Enums\ImportPipelineStatus;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Models\ImportPipelineTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ImportPipelineRepositoryInterface
{
    // Pipeline CRUD Operations
    public function create(array $data): ImportPipeline;

    public function findById(int $id): ?ImportPipeline;

    public function update(int $id, array $data): bool;

    public function delete(int $id): int;

    // Pipeline Queries
    public function getByTargetId(int $targetId): Collection;

    public function getActiveByTargetId(int $targetId): Collection;

    public function getScheduledPipelines(): Collection;

    public function getByFrequency(ImportPipelineFrequency $frequency): Collection;

    public function paginate(?int $targetId = null, int $perPage = 15, ?string $search = null): LengthAwarePaginator;

    public function search(string $query, ?int $targetId = null): Collection;

    // Execution Queries
    public function getExecutionsByPipeline(int $pipelineId, int $limit = 10): Collection;

    public function paginateExecutionsByPipeline(int $pipelineId, int $perPage = 15): LengthAwarePaginator;

    public function findExecutionById(int $id): ?\Elaitech\Import\Models\ImportPipelineExecution;

    public function getRecentExecutions(?int $targetId = null, int $limit = 20): Collection;

    public function getExecutionsByStatus(ImportPipelineStatus $status, ?int $targetId = null): Collection;

    public function getFailedExecutions(?int $targetId = null, int $limit = 10): Collection;

    public function getRunningExecutions(?int $targetId = null): Collection;

    // Statistics and Analytics
    public function getPipelineStats(?int $targetId = null): array;

    public function getExecutionStats(?int $targetId = null, int $days = 30): array;

    public function getPerformanceStats(?int $targetId = null, int $days = 30): array;

    // Template Operations
    public function getTemplates(bool $publicOnly = false): Collection;

    public function getTemplateById(int $id): ?ImportPipelineTemplate;

    public function createTemplate(array $data): ImportPipelineTemplate;

    // Dashboard Data
    public function getDashboardData(?int $targetId = null): array;
}
