<?php

declare(strict_types=1);

namespace Elaitech\Import\Models;

use App\Models\User;
use Elaitech\Import\Enums\ImportPipelineFrequency;
use Elaitech\Import\Enums\ImportPipelineStatus;
use Elaitech\Import\Enums\ImportPipelineStep;
use Elaitech\Import\Enums\PipelineStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Auth;

final class ImportPipeline extends Model
{
    use HasFactory;

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (ImportPipeline $pipeline): void {
            if (Auth::check() && ! $pipeline->created_by) {
                $pipeline->created_by = Auth::id();
            }
        });

        self::updating(function (ImportPipeline $pipeline): void {
            if (Auth::check()) {
                $pipeline->updated_by = Auth::id();
            }
        });
    }

    protected $fillable = [
        'name',
        'description',
        'target_id',
        'is_active',
        'start_time',
        'frequency',
        'last_executed_at',
        'next_execution_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_time' => 'datetime:H:i:s',
        'frequency' => ImportPipelineFrequency::class,
        'last_executed_at' => 'datetime',
        'next_execution_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $dates = [
        'start_time',
        'last_executed_at',
        'next_execution_at',
    ];

    // Relationships
    public function config(): HasMany
    {
        return $this->hasMany(ImportPipelineConfig::class, 'pipeline_id');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(ImportPipelineExecution::class, 'pipeline_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getDownloaderConfig(): ?ImportPipelineConfig
    {
        return $this->config()->where('type', ImportPipelineStep::DownloaderConfig->value)->first();
    }

    public function getReaderConfig(): ?ImportPipelineConfig
    {
        return $this->config()->where('type', ImportPipelineStep::ReaderConfig->value)->first();
    }

    public function getFilterConfig(): ?ImportPipelineConfig
    {
        return $this->config()->where('type', ImportPipelineStep::FilterConfig->value)->first();
    }

    public function getMapperConfig(): ?ImportPipelineConfig
    {
        return $this->config()->where('type', ImportPipelineStep::MapperConfig->value)->first();
    }

    public function getImagesPrepareConfig(): ?ImportPipelineConfig
    {
        return $this->config()->where('type', ImportPipelineStep::ImagesPrepareConfig->value)->first();
    }

    public function latestRunningExecution(): ?ImportPipelineExecution
    {
        return $this->executions()
            ->where('status', ImportPipelineStatus::RUNNING)
            ->latest()
            ->first();
    }

    public function logs(): HasManyThrough
    {
        return $this->hasManyThrough(
            ImportPipelineLog::class,
            ImportPipelineExecution::class,
            'pipeline_id', // Foreign key on executions table
            'execution_id', // Foreign key on logs table
            'id', // Local key on pipelines table
            'id' // Local key on executions table
        );
    }

    // Scopes
    #[Scope]
    public function active($query)
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    public function scheduled($query)
    {
        return $query->where('frequency', '!=', ImportPipelineFrequency::ONCE);
    }

    // Accessors & Mutators
    public function formattedStartTime(): Attribute
    {
        return Attribute::get(
            fn (mixed $value, array $attributes) => $this->start_time?->format('H:i') ?? 'Not set',
        );
    }

    // Helper methods
    public function isScheduled(): bool
    {
        return $this->frequency !== ImportPipelineFrequency::ONCE;
    }

    public function isConfigured(): bool
    {
        return $this->config()->count() > 4;
    }

    public function status(): Attribute
    {
        return Attribute::get(
            fn (mixed $value, array $attributes) => match (true) {
                ! $this->isConfigured() => PipelineStatus::NEEDS_CONFIGURATION,
                ! $this->is_active => PipelineStatus::INACTIVE,
                default => PipelineStatus::ACTIVE,
            },
        );
    }

}
