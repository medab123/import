<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\Contracts;

use Elaitech\Import\Services\Pipeline\DTOs\PrepareConfigurationData;
use Elaitech\Import\Services\Pipeline\DTOs\PrepareResultData;

/**
 * Prepare Service Interface
 *
 * Defines the contract for data preparation services that transform
 * mapped/filtered data before saving to the database.
 */
interface PrepareServiceInterface
{
    /**
     * Prepare data by applying configured transformations.
     *
     * @param  PrepareConfigurationData  $config  The preparation configuration
     * @return PrepareResultData The preparation result
     */
    public function prepare(PrepareConfigurationData $config): PrepareResultData;
}
