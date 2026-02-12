<?php

namespace Elaitech\Import;

use Elaitech\DataMapper\Contracts\DataMapperInterface;
use Elaitech\DataMapper\DataMapperService;
use Elaitech\Import\Contracts\Repositories\ImportPipeline\ImportPipelineRepositoryInterface;
use Elaitech\Import\Contracts\Services\ImportDashboard\ImportDashboardServiceInterface;
use Elaitech\Import\Services\ImportDashboard\ImportDashboardService;
use Elaitech\Import\Services\ImportDashboard\ImportPipelineRepository;
use Elaitech\Import\Services\Pipeline\Contracts\PipelineExecutionServiceInterface;
use Elaitech\Import\Services\Pipeline\Contracts\PipelineSchedulingServiceInterface;
use Elaitech\Import\Services\Pipeline\Factories\ImportPipelineConfigFactory;
use Elaitech\Import\Services\Pipeline\Services\PipelineExecutionService;
use Elaitech\Import\Services\Pipeline\Services\PipelineSchedulingService;
use Elaitech\Import\Services\Pipeline\Services\PipelineTestDataService;
use Elaitech\Import\Services\Providers\DownloaderServiceProvider;
use Elaitech\Import\Services\Providers\FilterServiceProvider;
use Elaitech\Import\Services\Providers\PipelineServiceProvider;
use Elaitech\Import\Services\Providers\PrepareServiceProvider;
use Elaitech\Import\Services\Providers\ReaderServiceProvider;
use Illuminate\Support\ServiceProvider;

final class ImportServiceProvider extends ServiceProvider
{
    public array $bindings = [
        ImportPipelineRepositoryInterface::class => ImportPipelineRepository::class,
        ImportDashboardServiceInterface::class => ImportDashboardService::class,
        ImportPipelineConfigFactory::class => ImportPipelineConfigFactory::class,
        PipelineExecutionServiceInterface::class => PipelineExecutionService::class,
        PipelineSchedulingServiceInterface::class => PipelineSchedulingService::class,
        PipelineTestDataService::class => PipelineTestDataService::class,
        DataMapperInterface::class => DataMapperService::class,
    ];

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->publishes([
            __DIR__ . '/../config/import-pipelines.php' => config_path('import-pipelines.php'),
        ], 'import-config');
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'import-migrations');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/import-pipelines.php',
            'import-pipelines'
        );

        $this->app->register(DownloaderServiceProvider::class);
        $this->app->register(ReaderServiceProvider::class);
        $this->app->register(FilterServiceProvider::class);
        $this->app->register(PrepareServiceProvider::class);
        $this->app->register(PipelineServiceProvider::class);
    }
}
