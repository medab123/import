<?php

declare(strict_types=1);

namespace Elaitech\Import\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ImportPipelineTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'template_data',
        'is_public',
        'created_by',
    ];

    protected $casts = [
        'template_data' => 'array',
        'is_public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }

    // Helper methods
    public function getTemplateValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->template_data, $key, $default);
    }

    public function setTemplateValue(string $key, mixed $value): void
    {
        $data = $this->template_data ?? [];
        data_set($data, $key, $value);
        $this->template_data = $data;
    }

    public function getDownloadConfig(): array
    {
        return $this->getTemplateValue('download', []);
    }

    public function getReadConfig(): array
    {
        return $this->getTemplateValue('read', []);
    }

    public function getMapConfig(): array
    {
        return $this->getTemplateValue('map', []);
    }

    public function getFilterConfig(): array
    {
        return $this->getTemplateValue('filter', []);
    }

    public function getOptionsConfig(): array
    {
        return $this->getTemplateValue('options', []);
    }

    public function createPipelineFromTemplate(int $targetId, string $name, ?string $description = null, ?int $createdBy = null): ImportPipeline
    {
        return ImportPipeline::create([
            'target_id' => $targetId,
            'name' => $name,
            'description' => $description ?? $this->description,
            'is_active' => true,
            'start_time' => $this->getTemplateValue('start_time'),
            'frequency' => $this->getTemplateValue('frequency', 'once'),
            'created_by' => $createdBy,
        ]);
    }
}
