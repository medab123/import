<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Providers;

use Elaitech\Import\Services\Core\Operators\FilterOperatorInterface;
use Elaitech\Import\Services\Filter\Contracts\FilterInterface;
use Elaitech\Import\Services\Filter\Contracts\FilterValidatorInterface;
use Elaitech\Import\Services\Filter\Contracts\OperatorRegistryInterface;
use Elaitech\Import\Services\Filter\Contracts\ValueExtractorInterface;
use Elaitech\Import\Services\Filter\Extractors\DotNotationValueExtractor;
use Elaitech\Import\Services\Filter\Implementations\BetweenOperator;
use Elaitech\Import\Services\Filter\Implementations\ContainsOperator;
use Elaitech\Import\Services\Filter\Implementations\DataFilterService;
use Elaitech\Import\Services\Filter\Implementations\EndsWithOperator;
use Elaitech\Import\Services\Filter\Implementations\EqualsOperator;
use Elaitech\Import\Services\Filter\Implementations\GreaterThanOperator;
use Elaitech\Import\Services\Filter\Implementations\InOperator;
use Elaitech\Import\Services\Filter\Implementations\IsNotNullOperator;
use Elaitech\Import\Services\Filter\Implementations\IsNullOperator;
use Elaitech\Import\Services\Filter\Implementations\LessThanOperator;
use Elaitech\Import\Services\Filter\Implementations\NotBetweenOperator;
use Elaitech\Import\Services\Filter\Implementations\NotContainsOperator;
use Elaitech\Import\Services\Filter\Implementations\NotEqualsOperator;
use Elaitech\Import\Services\Filter\Implementations\NotInOperator;
use Elaitech\Import\Services\Filter\Implementations\NotRegexOperator;
use Elaitech\Import\Services\Filter\Implementations\RegexOperator;
use Elaitech\Import\Services\Filter\Implementations\StartsWithOperator;
use Elaitech\Import\Services\Filter\Registry\OperatorRegistry;
use Elaitech\Import\Services\Filter\Validators\FilterValidator;
use Illuminate\Support\ServiceProvider;

final class FilterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerCoreServices();
        $this->registerOperators();
    }

    public function boot(): void
    {
        // Boot logic if needed
    }

    private function registerCoreServices(): void
    {
        $this->app->singleton(ValueExtractorInterface::class, DotNotationValueExtractor::class);

        $this->app->singleton(OperatorRegistryInterface::class, function ($app) {
            return new OperatorRegistry;
        });

        $this->app->singleton(FilterValidatorInterface::class, function ($app) {
            return new FilterValidator($app->make(OperatorRegistryInterface::class));
        });

        $this->app->singleton(FilterInterface::class, DataFilterService::class);
    }

    private function registerOperators(): void
    {
        $this->app->afterResolving(OperatorRegistryInterface::class, function (OperatorRegistryInterface $registry) {
            $operators = config('import-pipelines.filters.operators', []);

            foreach ($operators as $operator) {
                $operator = new $operator;
                if (!($operator instanceof FilterOperatorInterface)) {
                    throw new \InvalidArgumentException("Operator '{$operator}' should implement " . FilterOperatorInterface::class);
                }

                $registry->register($operator);
            }
        });
    }

}
