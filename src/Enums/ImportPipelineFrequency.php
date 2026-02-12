<?php

declare(strict_types=1);

namespace Elaitech\Import\Enums;

enum ImportPipelineFrequency: string
{
    case ONCE = 'once';
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';

    public function getLabel(): string
    {
        return match ($this) {
            self::ONCE => 'One Time',
            self::DAILY => 'Daily',
            self::WEEKLY => 'Weekly',
            self::MONTHLY => 'Monthly',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::ONCE => 'Execute once and stop',
            self::DAILY => 'Execute every day at specified time',
            self::WEEKLY => 'Execute weekly on specified days',
            self::MONTHLY => 'Execute monthly on specified date',
        };
    }

    public static function getOptions(): array
    {
        return array_map(
            fn (self $case) => [
                'value' => $case->value,
                'label' => $case->getLabel(),
                'description' => $case->getDescription(),
            ],
            self::cases()
        );
    }

    public static function fromString(string $value): self
    {
        return self::from($value);
    }
}
