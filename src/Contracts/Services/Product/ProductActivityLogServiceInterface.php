<?php

declare(strict_types=1);

namespace Elaitech\Import\Contracts\Services\Product;

use App\Data\Product\ProductActivityTimelineData;

interface ProductActivityLogServiceInterface
{
    public function getTimelineByUuid(string $uuid, int $perPage = 15): ProductActivityTimelineData;
}
