<?php 


declare(strict_types=1);

namespace Elaitech\Import\Services\Core\Contracts;
use Elaitech\Import\Services\Pipeline\DTOs\SaveResultData;
use Elaitech\Import\Services\Pipeline\DTOs\PipelinePassable;

interface ResultSaverInterface{
    public function save(PipelinePassable $passable,string|int $targetId): SaveResultData;
}