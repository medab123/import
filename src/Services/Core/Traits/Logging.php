<?php

namespace Elaitech\Import\Services\Core\Traits;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

trait Logging
{
    protected ?LoggerInterface $logger = null;

    /**
     * Set logger channel
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = Log::channel($logger);

        return $this;
    }

    public function logger(): LoggerInterface
    {
        return $this->logger ?? logger();
    }
}
