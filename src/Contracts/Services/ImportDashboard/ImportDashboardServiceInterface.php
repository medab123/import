<?php

declare(strict_types=1);

namespace Elaitech\Import\Contracts\Services\ImportDashboard;

use Elaitech\Import\Enums\ImportPipelineFrequency;
use Elaitech\Import\Enums\ImportPipelineStatus;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Models\ImportPipelineExecution;
use Elaitech\Import\Models\ImportPipelineTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ImportDashboardServiceInterface
{
    // Pipeline Management
    public function createPipeline(array $data): ImportPipeline;

    public function updatePipeline(int $id, array $data): bool;

    public function deletePipeline(int $id): bool;

    public function togglePipelineStatus(int $id): bool;

    public function savePipelineStep(?int $pipelineId, string $step, array $data): array;

    public function savePipelineDraft(?int $pipelineId, array $data): ImportPipeline;

    // Pipeline Queries
    public function getPipeline(int $id): ?ImportPipeline;

    public function getPipelinesByTargetId(int $targetId): Collection;

    public function getActivePipelinesByTargetId(int $targetId): Collection;

    public function getScheduledPipelines(): Collection;

    public function getPipelinesByFrequency(ImportPipelineFrequency $frequency): Collection;

    public function searchPipelines(string $query, ?int $targetId = null): Collection;

    public function paginatePipelines(?int $targetId = null, int $perPage = 15, ?string $search = null): LengthAwarePaginator;

    // Execution Management
    public function getExecutionsByPipeline(int $pipelineId, int $limit = 10): Collection;

    public function paginateExecutionsByPipeline(int $pipelineId, int $perPage = 15): LengthAwarePaginator;

    public function getExecution(int $id): ?ImportPipelineExecution;

    public function getRecentExecutions(?int $targetId = null, int $limit = 20): Collection;

    public function getFailedExecutions(?int $targetId = null, int $limit = 10): Collection;

    public function getRunningExecutions(?int $targetId = null): Collection;

    public function getExecutionsByStatus(ImportPipelineStatus $status, ?int $targetId = null): Collection;

    // Statistics and Analytics
    public function getPipelineStats(?int $targetId = null): array;

    public function getExecutionStats(?int $targetId = null, int $days = 30): array;

    // Company Management
    public function getAllCompanies(): Collection;

    public function getPerformanceStats(?int $targetId = null, int $days = 30): array;

    // Template Management
    public function getTemplates(bool $publicOnly = false): Collection;

    public function getTemplate(int $id): ?ImportPipelineTemplate;

    public function createTemplate(array $data): ImportPipelineTemplate;

    public function createPipelineFromTemplate(int $templateId, int $targetId, string $name, ?string $description = null, ?int $createdBy = null): ImportPipeline;

    // Dashboard Data
    public function getDashboardData(?int $targetId = null): array;

    public function getSystemHealth(): array;

    // Export Functions
    public function exportPipelines(?int $targetId = null): array;

    public function exportExecutions(?int $targetId = null, int $days = 30): array;
}
