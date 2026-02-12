<?php

declare(strict_types=1);

namespace Elaitech\Import\Models;

use Elaitech\Import\Enums\ImportPipelineStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ImportPipelineExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'pipeline_id',
        'status',
        'started_at',
        'completed_at',
        'total_rows',
        'processed_rows',
        'success_rate',
        'processing_time',
        'memory_usage',
        'error_message',
        'result_data',
    ];

    protected $casts = [
        'status' => ImportPipelineStatus::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'success_rate' => 'decimal:2',
        'processing_time' => 'decimal:3',
        'result_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(ImportPipeline::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ImportPipelineLog::class, 'execution_id');
    }

    // Scopes
    public function scopeByStatus($query, ImportPipelineStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', ImportPipelineStatus::COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', ImportPipelineStatus::FAILED);
    }

    public function scopeRunning($query)
    {
        return $query->where('status', ImportPipelineStatus::RUNNING);
    }

    public function scopePending($query)
    {
        return $query->where('status', ImportPipelineStatus::PENDING);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', ImportPipelineStatus::CANCELLED);
    }

    // Accessors
    public function getDurationAttribute(): ?float
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->completed_at, true);
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status->isFinished();
    }

    public function getIsRunningAttribute(): bool
    {
        return $this->status === ImportPipelineStatus::RUNNING;
    }

    public function getIsFailedAttribute(): bool
    {
        return $this->status->isFailed();
    }

    public function getIsSuccessfulAttribute(): bool
    {
        return $this->status->isSuccessful();
    }

    public function getIsCancelledAttribute(): bool
    {
        return $this->status->isCancelled();
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status->getLabel();
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status->getColor();
    }

    // Helper methods
    public function markAsCompleted(int $processedRows, int $totalRows, array $resultData = []): void
    {
        $this->update([
            'status' => ImportPipelineStatus::COMPLETED,
            'completed_at' => now(),
            'processed_rows' => $processedRows,
            'total_rows' => $totalRows,
            'success_rate' => $totalRows > 0 ? ($processedRows / $totalRows) * 100 : 0,
            'result_data' => $resultData,
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => ImportPipelineStatus::FAILED,
            'completed_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }

    public function markAsRunning(): void
    {
        $this->update([
            'status' => ImportPipelineStatus::RUNNING,
            'started_at' => now(),
        ]);
    }

    public function markAsCancelled(): void
    {
        $this->update([
            'status' => ImportPipelineStatus::CANCELLED,
            'completed_at' => now(),
        ]);
    }

    public function addLog(string $level, string $message, array $context = []): void
    {
        $this->logs()->create([
            'log_level' => $level,
            'message' => $message,
            'context' => $context,
        ]);
    }
}
