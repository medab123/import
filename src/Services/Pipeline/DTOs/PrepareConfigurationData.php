<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\DTOs;

use Spatie\LaravelData\Data;

/**
 * Prepare Configuration Data
 *
 * Configuration for the prepare stage, defining which transformations
 * should be applied to the data. Transformation configurations are now
 * stored in the import config file.
 */
final class PrepareConfigurationData extends Data
{
    /**
     * List of transformation names to apply.
     *
     * @var array<string>
     */
    public array $transformations = [];

    /**
     * @param  array<int, array<string, mixed>>  $data  The data to prepare
     * @param  int|null  $targetId  Target ID for context-dependent transformations
     * @param  array<string>  $transformations  List of transformation names to apply
     */
    public function __construct(
        public array $data,
        public ?int $targetId = null,
        array $transformations = [],
    ) {
        $this->transformations = $transformations ?: [
            'category',
            'generate_stock_id_from_vin',
            'generate_vin_from_stock_id',
            'title',
        ];
    }

    /**
     * Check if a specific transformation is enabled.
     */
    public function hasTransformation(string $transformation): bool
    {
        return in_array($transformation, $this->transformations, true);
    }
}
