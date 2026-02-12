<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Tests;

use Elaitech\Import\Services\Core\Cache\ImportCache;
use Elaitech\Import\Services\Core\Configuration\ImportConfig;
use Elaitech\Import\Services\Core\Exceptions\FactoryException;
use Elaitech\Import\Services\Core\Registry\ServiceRegistry;
use Elaitech\Import\Services\Downloader\Factories\DownloaderFactory;
use Elaitech\Import\Services\Reader\Factories\ReaderFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_reader_factory_validation(): void
    {
        $factory = new ReaderFactory;
        $factory->register(['csv' => \Elaitech\Import\Services\Reader\Implementations\CsvReader::class]);

        // Test valid reader
        $reader = $factory->for('csv');
        $this->assertInstanceOf(\Elaitech\Import\Services\Core\Contracts\ReaderInterface::class, $reader);

        // Test invalid type
        $this->expectException(FactoryException::class);
        $factory->for('invalid');
    }

    public function test_downloader_factory_validation(): void
    {
        $factory = new DownloaderFactory;
        $factory->register(['http' => \Elaitech\Import\Services\Downloader\Implementations\HttpDownloader::class]);

        // Test valid downloader
        $downloader = $factory->for('http');
        $this->assertInstanceOf(\Elaitech\Import\Services\Core\Contracts\DownloaderInterface::class, $downloader);

        // Test invalid scheme
        $this->expectException(FactoryException::class);
        $factory->for('invalid');
    }

    public function test_service_registry(): void
    {
        $registry = new ServiceRegistry;

        $readerFactory = new ReaderFactory;
        $readerFactory->register(['csv' => \Elaitech\Import\Services\Reader\Implementations\CsvReader::class]);

        $registry->registerFactory('reader', $readerFactory);

        $this->assertTrue($registry->hasFactory('reader'));
        $this->assertFalse($registry->hasFactory('downloader'));

        $factory = $registry->getFactory('reader');
        $this->assertInstanceOf(ReaderFactory::class, $factory);
    }

    public function test_import_config(): void
    {
        $config = ImportConfig::getInstance();

        $this->assertEquals(30, $config->get('default_timeout'));
        $this->assertTrue($config->has('max_file_size'));

        $config->set('custom_setting', 'value');
        $this->assertEquals('value', $config->get('custom_setting'));

        $errors = $config->validate();
        $this->assertEmpty($errors);
    }

    public function test_import_cache(): void
    {
        $cache = new ImportCache(app('cache'));

        $cache->put('test_key', 'test_value');
        $this->assertEquals('test_value', $cache->get('test_key'));

        $this->assertTrue($cache->has('test_key'));

        $cache->forget('test_key');
        $this->assertFalse($cache->has('test_key'));
    }

    public function test_factory_supports_method(): void
    {
        $factory = new ReaderFactory;
        $factory->register([
            'csv' => \Elaitech\Import\Services\Reader\Implementations\CsvReader::class,
            'json' => \Elaitech\Import\Services\Reader\Implementations\JsonReader::class,
        ]);

        $this->assertTrue($factory->supports('csv'));
        $this->assertTrue($factory->supports('CSV')); // Case insensitive
        $this->assertTrue($factory->supports('json'));
        $this->assertFalse($factory->supports('xml'));
    }

    public function test_get_available_types(): void
    {
        $factory = new ReaderFactory;
        $factory->register([
            'csv' => \Elaitech\Import\Services\Reader\Implementations\CsvReader::class,
            'json' => \Elaitech\Import\Services\Reader\Implementations\JsonReader::class,
        ]);

        $types = $factory->getAvailableTypes();
        $this->assertContains('csv', $types);
        $this->assertContains('json', $types);
        $this->assertCount(2, $types);
    }
}
