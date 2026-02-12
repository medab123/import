<?php

declare(strict_types=1);

namespace Elaitech\Import\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ImportPipelineLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'execution_id',
        'log_level',
        'message',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    public $timestamps = true;

    // Relationships
    public function execution(): BelongsTo
    {
        return $this->belongsTo(ImportPipelineExecution::class);
    }

    // Scopes
    public function scopeByLevel($query, string $level)
    {
        return $query->where('log_level', $level);
    }

    public function scopeErrors($query)
    {
        return $query->whereIn('log_level', ['error', 'critical']);
    }

    public function scopeWarnings($query)
    {
        return $query->where('log_level', 'warning');
    }

    public function scopeInfo($query)
    {
        return $query->where('log_level', 'info');
    }

    public function scopeDebug($query)
    {
        return $query->where('log_level', 'debug');
    }

    // Helper methods
    public function isError(): bool
    {
        return in_array($this->log_level, ['error', 'critical']);
    }

    public function isWarning(): bool
    {
        return $this->log_level === 'warning';
    }

    public function isInfo(): bool
    {
        return $this->log_level === 'info';
    }

    public function isDebug(): bool
    {
        return $this->log_level === 'debug';
    }

    public function getFormattedMessageAttribute(): string
    {
        $timestamp = $this->created_at->format('Y-m-d H:i:s');

        return "[{$timestamp}] [{$this->log_level}] {$this->message}";
    }
}
