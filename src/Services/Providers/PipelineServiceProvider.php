<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Providers;

use Elaitech\Import\Services\Pipeline\Contracts\ImportPipelineInterface;
use Elaitech\Import\Services\Pipeline\Contracts\PrepareServiceInterface;
use Elaitech\Import\Services\Pipeline\Implementations\ImportPipelineService;
use Elaitech\Import\Services\Pipeline\Orchestrators\PipelineOrchestrator;
use Elaitech\Import\Services\Prepare\Services\PrepareService;
use Illuminate\Support\ServiceProvider;

final class PipelineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PrepareServiceInterface::class, PrepareService::class);

        $this->app->singleton(PipelineOrchestrator::class);

        $this->app->singleton(ImportPipelineInterface::class, ImportPipelineService::class);
    }
}
