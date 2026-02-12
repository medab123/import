<?php

declare(strict_types=1);

namespace Elaitech\Import\Enums;

enum ImportPipelineStatus: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::RUNNING => 'Running',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::PENDING => 'Pipeline is waiting to be executed',
            self::RUNNING => 'Pipeline is currently running',
            self::COMPLETED => 'Pipeline completed successfully',
            self::FAILED => 'Pipeline execution failed',
            self::CANCELLED => 'Pipeline execution was cancelled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::RUNNING => 'blue',
            self::COMPLETED => 'green',
            self::FAILED => 'red',
            self::CANCELLED => 'yellow',
        };
    }

    public function isActive(): bool
    {
        return match ($this) {
            self::PENDING, self::RUNNING => true,
            self::COMPLETED, self::FAILED, self::CANCELLED => false,
        };
    }

    public function isFinished(): bool
    {
        return match ($this) {
            self::COMPLETED, self::FAILED, self::CANCELLED => true,
            self::PENDING, self::RUNNING => false,
        };
    }

    public function isSuccessful(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }

    public function isCancelled(): bool
    {
        return $this === self::CANCELLED;
    }

    public static function getOptions(): array
    {
        return array_map(
            fn (self $case) => [
                'value' => $case->value,
                'label' => $case->getLabel(),
                'description' => $case->getDescription(),
                'color' => $case->getColor(),
            ],
            self::cases()
        );
    }

    public static function fromString(string $value): self
    {
        return self::from($value);
    }
}
