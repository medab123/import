<?php

declare(strict_types=1);

namespace Elaitech\Import\Tests;

use Elaitech\DataMapper\DataMapperServiceProvider;
use Elaitech\Import\ImportServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Spatie\LaravelData\LaravelDataServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDataServiceProvider::class,
            ActivitylogServiceProvider::class,
            DataMapperServiceProvider::class,
            ImportServiceProvider::class,
        ];
    }
}
