<?php

declare(strict_types=1);

namespace Elaitech\Import\Enums;

enum PipelineStage: string
{
    case DOWNLOAD = 'download';
    case READ = 'read';
    case FILTER = 'filter';
    case MAP = 'map';
    case IMAGES_PREPARE = 'images_prepare';
    case PREPARE = 'prepare';
    case SAVE = 'save';

    public function label(): string
    {
        return match ($this) {
            self::DOWNLOAD => 'Download',
            self::READ => 'Read',
            self::FILTER => 'Filter',
            self::MAP => 'Map',
            self::IMAGES_PREPARE => 'Images Prepare',
            self::PREPARE => 'Prepare',
            self::SAVE => 'Save',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::DOWNLOAD => 'Download data from source',
            self::READ => 'Read and parse downloaded data',
            self::FILTER => 'Filter mapped data',
            self::MAP => 'Map data to target structure',
            self::IMAGES_PREPARE => 'Images Prepare',
            self::PREPARE => 'Prepare and transform data before saving',
            self::SAVE => 'Save data to database',
        };
    }

    public function order(): int
    {
        return match ($this) {
            self::DOWNLOAD => 1,
            self::READ => 2,
            self::FILTER => 3,
            self::MAP => 4,
            self::IMAGES_PREPARE => 5,
            self::PREPARE => 6,
            self::SAVE => 7,
        };
    }

    public static function fromOrder(int $order): ?self
    {
        return match ($order) {
            1 => self::DOWNLOAD,
            2 => self::READ,
            3 => self::FILTER,
            4 => self::MAP,
            5 => self::IMAGES_PREPARE,
            6 => self::PREPARE,
            7 => self::SAVE,
            default => null,
        };
    }

    public static function all(): array
    {
        return [
            self::DOWNLOAD,
            self::READ,
            self::FILTER,
            self::MAP,
            self::IMAGES_PREPARE,
            self::PREPARE,
            self::SAVE,
        ];
    }

    public function getNextStage(): ?self
    {
        return self::fromOrder($this->order() + 1);
    }

    public function getPreviousStage(): ?self
    {
        return self::fromOrder($this->order() - 1);
    }
}
