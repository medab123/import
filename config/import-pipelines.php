<?php

declare(strict_types=1);

use Elaitech\Import\Services\Downloader\Implementations\FtpDownloader;
use Elaitech\Import\Services\Downloader\Implementations\HttpDownloader;
use Elaitech\Import\Services\Downloader\Implementations\SftpDownloader;
use Elaitech\Import\Services\Filter\Implementations\BetweenOperator;
use Elaitech\Import\Services\Filter\Implementations\ContainsOperator;
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
use Elaitech\Import\Services\Reader\Implementations\CsvReader;
use Elaitech\Import\Services\Reader\Implementations\JsonReader;
use Elaitech\Import\Services\Reader\Implementations\XmlReader;

return [

    'downloaders' => [
        'http' => HttpDownloader::class,
        'https' => HttpDownloader::class,
        'ftp' => FtpDownloader::class,
        'sftp' => SftpDownloader::class,
    ],

    'prepare' => [
        'using' => null,
    ],

    'filters' => [
        'operators' => [
            EqualsOperator::class,
            NotEqualsOperator::class,
            ContainsOperator::class,
            NotContainsOperator::class,
            RegexOperator::class,
            NotRegexOperator::class,
            GreaterThanOperator::class,
            LessThanOperator::class,
            InOperator::class,
            NotInOperator::class,
            BetweenOperator::class,
            NotBetweenOperator::class,
            IsNullOperator::class,
            IsNotNullOperator::class,
            StartsWithOperator::class,
            EndsWithOperator::class,
        ]
    ],

    'readers' => [
        'csv' => CsvReader::class,
        'json' => JsonReader::class,
        'xml' => XmlReader::class,
    ],


    'queues' => [
        'default' => env('IMPORT_PIPELINES_QUEUE', 'import-pipelines'),
        'high_priority' => env('IMPORT_PIPELINES_HIGH_PRIORITY_QUEUE', 'import-pipelines-high'),
        'low_priority' => env('IMPORT_PIPELINES_LOW_PRIORITY_QUEUE', 'import-pipelines-low'),
    ],

    'timeouts' => [
        'default' => env('IMPORT_PIPELINES_TIMEOUT', 5600),
        'large_files' => env('IMPORT_PIPELINES_LARGE_FILE_TIMEOUT', 7200),
        'small_files' => env('IMPORT_PIPELINES_SMALL_FILE_TIMEOUT', 1800),
    ],

    'retry' => [
        'max_attempts' => env('IMPORT_PIPELINES_RETRY_ATTEMPTS', 1),
        'backoff' => env('IMPORT_PIPELINES_BACKOFF', 60),
        'max_exceptions' => env('IMPORT_PIPELINES_MAX_EXCEPTIONS', 3),
    ],

    'memory' => [
        'default' => env('IMPORT_PIPELINES_MEMORY', 512),
        'large_files' => env('IMPORT_PIPELINES_LARGE_FILE_MEMORY', 1024),
    ],

    'scheduling' => [
        'tolerance_minutes' => env('IMPORT_PIPELINES_TOLERANCE_MINUTES', 5),
        'custom_interval_hours' => env('IMPORT_PIPELINES_CUSTOM_INTERVAL_HOURS', 24),
    ],

    'logging' => [
        'level' => env('IMPORT_PIPELINES_LOG_LEVEL', 'info'),
        'channels' => [
            'execution' => env('IMPORT_PIPELINES_EXECUTION_LOG_CHANNEL', 'single'),
            'scheduling' => env('IMPORT_PIPELINES_SCHEDULING_LOG_CHANNEL', 'single'),
        ],
    ],
];
