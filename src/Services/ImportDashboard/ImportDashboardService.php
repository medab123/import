<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\ImportDashboard;

use Elaitech\Import\Contracts\Repositories\ImportPipeline\ImportPipelineRepositoryInterface;
use Elaitech\Import\Contracts\Services\ImportDashboard\ImportDashboardServiceInterface;
use Elaitech\Import\Enums\ImportPipelineFrequency;
use Elaitech\Import\Enums\ImportPipelineStatus;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Models\ImportPipelineExecution;
use Elaitech\Import\Models\ImportPipelineTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

final readonly class ImportDashboardService implements ImportDashboardServiceInterface
{
    public function __construct(
        private ImportPipelineRepositoryInterface $repository,
    ) {}

    // Pipeline Management
    public function createPipeline(array $data): ImportPipeline
    {
        try {
            $pipeline = $this->repository->create($data);

            Log::info('Pipeline created', [
                'pipeline_id' => $pipeline->id,
                'name' => $pipeline->name,
                'target_id' => $pipeline->target_id,
            ]);

            return $pipeline;
        } catch (\Exception $e) {
            Log::error('Failed to create pipeline', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function updatePipeline(int $id, array $data): bool
    {
        try {
            $result = $this->repository->update($id, $data);

            if ($result) {
                Log::info('Pipeline updated', [
                    'pipeline_id' => $id,
                    'data' => $data,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to update pipeline', [
                'pipeline_id' => $id,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function deletePipeline(int $id): bool
    {
        try {
            $pipeline = $this->repository->findById($id);
            if (! $pipeline) {
                return false;
            }

            $result = $pipeline->delete();

            if ($result) {
                Log::info('Pipeline deleted', [
                    'pipeline_id' => $id,
                    'name' => $pipeline->name,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to delete pipeline', [
                'pipeline_id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function togglePipelineStatus(int $id): bool
    {
        try {
            $pipeline = $this->repository->findById($id);
            if (! $pipeline) {
                return false;
            }

            $newStatus = ! $pipeline->is_active;
            $result = $this->repository->update($id, ['is_active' => $newStatus]);

            if ($result) {
                Log::info('Pipeline status toggled', [
                    'pipeline_id' => $id,
                    'new_status' => $newStatus ? 'active' : 'inactive',
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to toggle pipeline status', [
                'pipeline_id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function savePipelineStep(?int $pipelineId, string $step, array $data): array
    {
        try {
            // If we have a pipeline ID, update the existing pipeline
            if ($pipelineId) {
                $pipeline = $this->repository->findById($pipelineId);

                if (! $pipeline) {
                    throw new \Exception('Pipeline not found');
                }

                $configField = match ($step) {
                    'basicInfo' => null, // Basic info updates the main fields
                    'downloaderConfig' => 'downloader_config',
                    'readerConfig' => 'reader_config',
                    'filterConfig' => 'filter_config',
                    'mapperConfig' => 'mapper_config',
                    'outputConfig' => 'output_config',
                    default => throw new \InvalidArgumentException("Invalid step: {$step}")
                };

                if ($configField) {
                    $this->repository->update($pipelineId, [
                        $configField => $data,
                        'updated_by' => auth()->id(),
                    ]);
                } else {
                    // Update basic info fields
                    $this->repository->update($pipelineId, [
                        'name' => $data['name'] ?? $pipeline->name,
                        'description' => $data['description'] ?? $pipeline->description,
                        'target_id' => $data['target_id'] ?? $pipeline->target_id,
                        'frequency' => $data['frequency'] ?? $pipeline->frequency,
                        'custom_schedule' => $data['custom_schedule'] ?? $pipeline->custom_schedule,
                        'priority' => $data['priority'] ?? $pipeline->priority,
                        'is_active' => $data['auto_start'] ?? $pipeline->is_active,
                        'updated_by' => auth()->id(),
                    ]);
                }

                Log::info('Pipeline step saved', [
                    'pipeline_id' => $pipelineId,
                    'step' => $step,
                ]);

                return $data;
            } else {
                // Create a new pipeline for the first step (basicInfo)
                if ($step === 'basicInfo') {
                    $pipelineData = [
                        'name' => $data['name'],
                        'description' => $data['description'] ?? '',
                        'target_id' => $data['target_id'],
                        'frequency' => $data['frequency'],
                        'custom_schedule' => $data['custom_schedule'] ?? null,
                        'priority' => $data['priority'] ?? 'normal',
                        'is_active' => $data['auto_start'] ?? false,
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ];

                    $pipeline = $this->repository->create($pipelineData);

                    Log::info('Pipeline created from basic info step', [
                        'pipeline_id' => $pipeline->id,
                        'name' => $pipeline->name,
                        'target_id' => $pipeline->target_id,
                    ]);

                    return [
                        'pipeline' => $pipeline,
                        'data' => $data,
                    ];
                } else {
                    throw new \Exception('Cannot save step without pipeline ID');
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to save pipeline step', [
                'pipeline_id' => $pipelineId,
                'step' => $step,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function savePipelineDraft(?int $pipelineId, array $data): ImportPipeline
    {
        try {
            if ($pipelineId) {
                // Update existing draft
                $pipeline = $this->repository->findById($pipelineId);

                if (! $pipeline) {
                    throw new \Exception('Pipeline not found');
                }

                $this->repository->update($pipelineId, [
                    'name' => $data['basicInfo']['name'] ?? $pipeline->name,
                    'description' => $data['basicInfo']['description'] ?? $pipeline->description,
                    'source_config' => $data['sourceConfig'] ?? $pipeline->source_config,
                    'data_processing' => $data['dataProcessing'] ?? $pipeline->data_processing,
                    'output_config' => $data['outputConfig'] ?? $pipeline->output_config,
                    'updated_by' => auth()->id(),
                ]);

                return $pipeline->fresh();
            } else {
                // Create new draft
                $pipeline = $this->repository->create([
                    'name' => $data['basicInfo']['name'] ?? 'Draft Pipeline',
                    'description' => $data['basicInfo']['description'] ?? '',
                    'target_id' => $data['basicInfo']['target_id'] ?? 1,
                    'frequency' => $data['basicInfo']['frequency'] ?? 'once',
                    'priority' => $data['basicInfo']['priority'] ?? 'normal',
                    'is_active' => false,
                    'source_config' => $data['sourceConfig'] ?? [],
                    'data_processing' => $data['dataProcessing'] ?? [],
                    'output_config' => $data['outputConfig'] ?? [],
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                Log::info('Pipeline draft created', [
                    'pipeline_id' => $pipeline->id,
                    'name' => $pipeline->name,
                ]);

                return $pipeline;
            }
        } catch (\Exception $e) {
            Log::error('Failed to save pipeline draft', [
                'pipeline_id' => $pipelineId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // Pipeline Queries
    public function getPipeline(int $id): ?ImportPipeline
    {
        return $this->repository->findById($id);
    }

    public function getPipelinesByTargetId(int $targetId): Collection
    {
        return $this->repository->getByTargetId($targetId);
    }

    public function getActivePipelinesByTargetId(int $targetId): Collection
    {
        return $this->repository->getActiveByTargetId($targetId);
    }

    public function getScheduledPipelines(): Collection
    {
        return $this->repository->getScheduledPipelines();
    }

    public function getPipelinesByFrequency(ImportPipelineFrequency $frequency): Collection
    {
        return $this->repository->getByFrequency($frequency);
    }

    public function searchPipelines(string $query, ?int $targetId = null): Collection
    {
        return $this->repository->search($query, $targetId);
    }

    public function paginatePipelines(?int $targetId = null, int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return $this->repository->paginate($targetId, $perPage, $search);
    }

    // Execution Management
    public function getExecutionsByPipeline(int $pipelineId, int $limit = 10): Collection
    {
        return $this->repository->getExecutionsByPipeline($pipelineId, $limit);
    }

    public function paginateExecutionsByPipeline(int $pipelineId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginateExecutionsByPipeline($pipelineId, $perPage);
    }

    public function getExecution(int $id): ?ImportPipelineExecution
    {
        return $this->repository->findExecutionById($id);
    }

    public function getRecentExecutions(?int $targetId = null, int $limit = 20): Collection
    {
        return $this->repository->getRecentExecutions($targetId, $limit);
    }

    public function getFailedExecutions(?int $targetId = null, int $limit = 10): Collection
    {
        return $this->repository->getFailedExecutions($targetId, $limit);
    }

    public function getRunningExecutions(?int $targetId = null): Collection
    {
        return $this->repository->getRunningExecutions($targetId);
    }

    public function getExecutionsByStatus(ImportPipelineStatus $status, ?int $targetId = null): Collection
    {
        return $this->repository->getExecutionsByStatus($status, $targetId);
    }

    // Statistics and Analytics
    public function getPipelineStats(?int $targetId = null): array
    {
        return $this->repository->getPipelineStats($targetId);
    }

    public function getExecutionStats(?int $targetId = null, int $days = 30): array
    {
        return $this->repository->getExecutionStats($targetId, $days);
    }

    public function getPerformanceStats(?int $targetId = null, int $days = 30): array
    {
        return $this->repository->getPerformanceStats($targetId, $days);
    }

    // Template Management
    public function getTemplates(bool $publicOnly = false): Collection
    {
        return $this->repository->getTemplates($publicOnly);
    }

    public function getTemplate(int $id): ?ImportPipelineTemplate
    {
        return $this->repository->getTemplateById($id);
    }

    public function createTemplate(array $data): ImportPipelineTemplate
    {
        try {
            $template = $this->repository->createTemplate($data);

            Log::info('Template created', [
                'template_id' => $template->id,
                'name' => $template->name,
                'is_public' => $template->is_public,
            ]);

            return $template;
        } catch (\Exception $e) {
            Log::error('Failed to create template', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function createPipelineFromTemplate(int $templateId, int $targetId, string $name, ?string $description = null, ?int $createdBy = null): ImportPipeline
    {
        try {
            $template = $this->getTemplate($templateId);
            if (! $template) {
                throw new \Exception("Template not found with ID: {$templateId}");
            }

            $pipeline = $template->createPipelineFromTemplate($targetId, $name, $description, $createdBy);

            Log::info('Pipeline created from template', [
                'pipeline_id' => $pipeline->id,
                'template_id' => $templateId,
                'target_id' => $targetId,
            ]);

            return $pipeline;
        } catch (\Exception $e) {
            Log::error('Failed to create pipeline from template', [
                'template_id' => $templateId,
                'target_id' => $targetId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // Dashboard Data
    public function getDashboardData(?int $targetId = null): array
    {
        try {
            return $this->repository->getDashboardData($targetId);
        } catch (\Exception $e) {
            Log::error('Failed to get dashboard data', [
                'target_id' => $targetId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // Health Check
    public function getSystemHealth(): array
    {
        try {
            $runningExecutions = $this->getRunningExecutions();
            $failedExecutions = $this->getFailedExecutions(null, 5);
            $scheduledPipelines = $this->getScheduledPipelines();

            $health = [
                'status' => 'healthy',
                'running_executions' => $runningExecutions->count(),
                'recent_failures' => $failedExecutions->count(),
                'scheduled_pipelines' => $scheduledPipelines->count(),
                'last_check' => now()->toISOString(),
            ];

            // Check for issues
            if ($failedExecutions->count() > 10) {
                $health['status'] = 'warning';
                $health['issues'][] = 'High number of recent failures';
            }

            if ($runningExecutions->count() > 50) {
                $health['status'] = 'warning';
                $health['issues'][] = 'High number of running executions';
            }

            return $health;
        } catch (\Exception $e) {
            Log::error('Failed to get system health', [
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'last_check' => now()->toISOString(),
            ];
        }
    }

    // Export Data
    public function exportPipelines(?int $targetId = null): array
    {
        $pipelines = $targetId
            ? $this->getPipelinesByTargetId($targetId)
            : $this->repository->paginate(1000)->items();

        return $pipelines->map(function ($pipeline) {
            return [
                'id' => $pipeline->id,
                'name' => $pipeline->name,
                'description' => $pipeline->description,
                'company' => $pipeline->company->name ?? 'N/A',
                'frequency' => $pipeline->frequency->getLabel(),
                'start_time' => $pipeline->formatted_start_time,
                'is_active' => $pipeline->is_active ? 'Yes' : 'No',
                'created_by' => $pipeline->creator->name ?? 'N/A',
                'created_at' => $pipeline->created_at->format('Y-m-d H:i:s'),
                'last_execution' => $pipeline->executions->first()?->created_at?->format('Y-m-d H:i:s') ?? 'Never',
            ];
        })->toArray();
    }

    public function exportExecutions(?int $targetId = null, int $days = 30): array
    {
        $executions = $this->getRecentExecutions($targetId, 1000);

        return $executions->map(function ($execution) {
            return [
                'id' => $execution->id,
                'pipeline_name' => $execution->pipeline->name,
                'company' => $execution->pipeline->company->name ?? 'N/A',
                'status' => $execution->status_label,
                'total_rows' => $execution->total_rows,
                'processed_rows' => $execution->processed_rows,
                'success_rate' => $execution->success_rate.'%',
                'processing_time' => $execution->processing_time.'s',
                'memory_usage' => $execution->memory_usage.'MB',
                'started_at' => $execution->started_at?->format('Y-m-d H:i:s') ?? 'N/A',
                'completed_at' => $execution->completed_at?->format('Y-m-d H:i:s') ?? 'N/A',
                'error_message' => $execution->error_message ?? 'N/A',
            ];
        })->toArray();
    }

    // Company Management
    public function getAllCompanies(): Collection
    {
        return $this->companyService->getAllCompanies();
    }
}
