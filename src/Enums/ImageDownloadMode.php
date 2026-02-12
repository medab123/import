<?php

declare(strict_types=1);

namespace Elaitech\Import\Enums;

enum ImageDownloadMode: string
{
    case ALL = 'all';
    case NEW_PRODUCTS_ONLY = 'new_products_only';
    case PRODUCTS_WITHOUT_IMAGES = 'products_without_images';

    public function label(): string
    {
        return match ($this) {
            self::ALL => 'All Products',
            self::NEW_PRODUCTS_ONLY => 'Just for New Products',
            self::PRODUCTS_WITHOUT_IMAGES => 'Just Products Without Images',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ALL => 'Download images for all products',
            self::NEW_PRODUCTS_ONLY => 'Download images only for newly imported products',
            self::PRODUCTS_WITHOUT_IMAGES => 'Download images only for products that don\'t have images',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
