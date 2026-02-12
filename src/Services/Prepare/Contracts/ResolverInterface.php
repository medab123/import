<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Prepare\Contracts;

use Elaitech\Import\Services\Pipeline\DTOs\PrepareConfigurationData;

/**
 * Resolver Interface
 *
 * Defines the contract for data transformation resolvers used in the prepare stage.
 */
interface ResolverInterface
{
    /**
     * handle and transform a single row of data.
     *
     * @param  array<string, mixed>  $row  The row data to transform
     * @param  PrepareConfigurationData  $config  The preparation configuration
     * @return array<string, mixed> The transformed row
     */
    public function handle(array $row, PrepareConfigurationData $config): array;
}
