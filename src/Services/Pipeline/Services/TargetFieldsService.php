<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\Services;

use Str;

final class TargetFieldsService
{
    protected array $fieldsToExclude = [];

    public function getTargetFields(): array
    {
        $organization = null;
        
        if (app()->has('organization')) {
            $organization = app('organization');
        }

        if (!$organization) {
            return [];
        }

        return \App\Models\TargetField::where('organization_uuid', $organization->uuid)
            ->get()
            ->map(fn ($item) => [
                "field" => $item->field,
                "label" => $item->label,
                "category" => $item->category,
                "description" => $item->description,
                "type" => $item->type,
                "model" => $item->model
            ])
            ->toArray();
    }

}
