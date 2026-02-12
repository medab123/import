<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Providers;

use Elaitech\Import\Services\Core\Configuration\ImportConfig;
use Elaitech\Import\Services\Core\Registry\ServiceRegistry;
use Illuminate\Support\ServiceProvider;

abstract class BaseImportServiceProvider extends ServiceProvider
{
    protected ?ImportConfig $config = null;

    protected ?ServiceRegistry $registry = null;

    /**
     * Get the service mappings for this provider.
     * Should return an array of [type => class] mappings.
     */
    abstract protected function getServiceMappings(): array;

    /**
     * Get the factory class for this provider.
     */
    abstract protected function getFactoryClass(): string;

    /**
     * Get the factory interface for this provider.
     */
    abstract protected function getFactoryInterface(): string;

    /**
     * Get the service type name for this provider (e.g., 'reader', 'downloader').
     */
    abstract protected function getServiceType(): string;

    /**
     * Register additional services that need special dependency injection.
     * Override this method if you need custom service registration.
     */
    protected function registerCustomServices(): void
    {
        // Override in child classes if needed
    }

    /**
     * Boot the service provider.
     */
    public function boot(): void
    {
        $this->loadConfiguration();
        $this->registerOptionDefinitions();
    }

    public function register(): void
    {
        $this->registerCustomServices();
        $this->registerServices();
        $this->registerFactory();
        $this->registerRegistry();
    }

    protected function registerServices(): void
    {
        $mappings = $this->getServiceMappings();

        foreach ($mappings as $type => $serviceClass) {
            $this->app->singleton($serviceClass);
        }
    }

    protected function registerFactory(): void
    {
        $this->app->singleton($this->getFactoryInterface(), function ($app) {
            $factoryClass = $this->getFactoryClass();

            return app($factoryClass)->register($this->getServiceMappings());
        });
    }

    protected function registerRegistry(): void
    {
        $this->app->singleton(ServiceRegistry::class);

        $this->app->afterResolving(ServiceRegistry::class, function (ServiceRegistry $registry) {
            $factory = $this->app->make($this->getFactoryInterface());
            $registry->registerFactory($this->getServiceType(), $factory);
        });
    }

    protected function loadConfiguration(): void
    {
        $this->config = ImportConfig::getInstance();

        // Load configuration from config files if they exist
        if ($this->app->bound('config')) {
            $configKey = "import-pipeline.{$this->getServiceType()}";
            if ($this->app->make('config')->has($configKey)) {
                $this->config->merge($this->app->make('config')->get($configKey, []));
            }
        }
    }

    protected function registerOptionDefinitions(): void
    {
        // This can be overridden in child classes to register option definitions
    }

    /**
     * Get the configuration instance.
     */
    protected function getConfig(): ImportConfig
    {
        return $this->config ?? ImportConfig::getInstance();
    }

    /**
     * Get the service registry instance.
     */
    protected function getRegistry(): ServiceRegistry
    {
        return $this->registry ?? $this->app->make(ServiceRegistry::class);
    }
}
