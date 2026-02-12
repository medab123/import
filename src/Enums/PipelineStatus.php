<?php

declare(strict_types=1);

namespace Elaitech\Import\Enums;

enum PipelineStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case NEEDS_CONFIGURATION = 'needs_configuration';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::NEEDS_CONFIGURATION => 'Needs configuration',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ACTIVE => 'CheckCircle2',
            self::INACTIVE => 'Circle',
            self::NEEDS_CONFIGURATION => 'Settings',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::ACTIVE => 'default',
            self::INACTIVE => 'secondary',
            self::NEEDS_CONFIGURATION => 'default',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::ACTIVE => 'bg-green-500/10 text-green-700 dark:text-green-400 border-green-500/20',
            self::INACTIVE => 'bg-muted text-muted-foreground',
            self::NEEDS_CONFIGURATION => 'bg-yellow-500/10 text-yellow-700 dark:text-yellow-400 border-yellow-500/20',
        };
    }

    /**
     * Convenience helper for ViewModels / API responses.
     *
     * @return array{
     *     variant: string,
     *     text: string,
     *     icon: string,
     *     class: string
     * }
     */
    public function toBadgeConfig(): array
    {
        return [
            'name' => $this->name,
            'variant' => $this->badgeVariant(),
            'text' => $this->label(),
            'icon' => $this->icon(),
            'class' => $this->badgeClass(),
        ];
    }
}
