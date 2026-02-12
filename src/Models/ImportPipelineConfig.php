<?php

declare(strict_types=1);

namespace Elaitech\Import\Models;

use Elaitech\Import\Enums\ImportPipelineStep;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

final class ImportPipelineConfig extends Model
{
    use HasFactory, LogsActivity;

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        self::created(function (ImportPipelineConfig $config): void {
            $config->updateParentPipeline();
        });

        self::updated(function (ImportPipelineConfig $config): void {
            $config->updateParentPipeline();
        });

        self::deleted(function (ImportPipelineConfig $config): void {
            $config->updateParentPipeline();
        });
    }

    /**
     * Update the parent pipeline's updated_by field.
     */
    protected function updateParentPipeline(): void
    {
        if (Auth::check() && $this->pipeline_id) {
            $pipeline = $this->pipeline;
            if ($pipeline) {
                $pipeline->update([
                    'updated_by' => Auth::id(),
                ]);
            }
        }
    }

    protected $fillable = [
        'pipeline_id',
        'type',
        'config_data',
    ];

    protected $casts = [
        'type' => ImportPipelineStep::class,
        'config_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(ImportPipeline::class);
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->subject_type = ImportPipeline::class;
        $activity->subject_id = $this->pipeline_id;

        $properties = $activity->properties->toArray();

        $new = $properties['attributes'] ?? [];
        $old = $properties['old'] ?? [];

        $changes = arrayDiffRecursiveSpatie($new, $old);

        $activity->properties = collect([
            'attributes' => $changes['attributes'],
            'old' => $changes['old'],
            'config_id' => $this->id,
            'config_type' => $this->type->value,
            'event' => $eventName,
        ]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('pipeline_config')
            ->setDescriptionForEvent(fn (string $eventName) => "Pipeline config {$this->type->value} has been {$eventName}");
    }
}
