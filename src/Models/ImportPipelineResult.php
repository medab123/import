<?php

declare(strict_types=1);

namespace Elaitech\Import\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ImportPipelineResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_uuid',
        'pipeline_id',
        'execution_id',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(ImportPipeline::class);
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(ImportPipelineExecution::class);
    }
}
