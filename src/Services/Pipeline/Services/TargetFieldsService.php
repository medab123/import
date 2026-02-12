<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\Services;

use Str;

final class TargetFieldsService
{
    protected array $fieldsToExclude = [
        'id',
        'uuid',
        'productable_type',
        'productable_id',
        'created_at',
        'updated_at',
        'deleted_at',
        'category_id',
        'company_id',
        'created_by',
        'updated_by',
        'categorizable_type',
        'categorizable_id',
    ];

    /**
     * Get all available target fields from product and vehicle models.
     * {
     * "field": "title",
     * "label": "Title",
     * "category": "Product",
     * "description": "Product Title field",
     * "type": "string",
     * "model": "Product"
     * }
     */
    public function getTargetFields(): array
    {
        return [[
            "field" => "title",
            "label" => "Title",
            "category" => "Product",
            "description" => "Product Title field",
            "type" => "string",
            "model" => "Product"
        ]];
    }

}
