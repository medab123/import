# 📦 Elaitech Import

A comprehensive, modular **data import workflow engine** for Laravel 12. Provides a full pipeline system for downloading, reading, filtering, mapping, transforming, and processing data from external sources — with execution tracking, scheduling, and a built-in dashboard.

> **Namespace:** `Elaitech\Import`  
> **Requires:** PHP 8.4+ · Laravel 12 · `elaitech/data-mapper`

---

## 📖 Table of Contents

- [Installation](#-installation)
- [Architecture](#-architecture)
- [Pipeline System](#-pipeline-system)
- [Models](#-models)
- [Services](#-services)
- [Enums](#-enums)
- [Configuration](#-configuration)
- [Extending](#-extending)
- [Testing](#-testing)
- [License](#-license)

---

## 🚀 Installation

### As a local Composer package (recommended)

In your root `composer.json`, add the package as a path repository:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/import",
            "options": { "symlink": true }
        }
    ],
    "require": {
        "elaitech/import": "@dev"
    }
}
```

Then install:

```bash
composer update elaitech/import
```

### Publishing Assets

```bash
# Publish configuration
php artisan vendor:publish --tag=import-config

# Publish migrations (optional — they auto-load)
php artisan vendor:publish --tag=import-migrations

# Run migrations
php artisan migrate
```

---

## 🏗 Architecture

```
src/
├── ImportServiceProvider.php         # Package bootstrap — registers all sub-providers
├── Helpers.php                       # Global helper functions
│
├── Models/                           # Eloquent models (5)
│   ├── ImportPipeline.php            # Main pipeline definition
│   ├── ImportPipelineConfig.php      # Per-step JSON configuration
│   ├── ImportPipelineExecution.php   # Execution run records
│   ├── ImportPipelineLog.php         # Per-stage log entries
│   └── ImportPipelineTemplate.php    # Reusable pipeline templates
│
├── Contracts/                        # Top-level interfaces
│   ├── Repositories/                 # Repository contracts
│   └── Services/                     # Service contracts
│
├── Enums/                            # Backed enums (6)
│   ├── ImportPipelineStatus.php      # pending, running, completed, failed, cancelled
│   ├── ImportPipelineFrequency.php   # once, daily, weekly, monthly
│   ├── ImportPipelineStep.php        # 8 stepper steps
│   ├── PipelineStage.php            # Pipeline execution stages
│   ├── PipelineStatus.php           # active, inactive, needs_configuration
│   └── ImageDownloadMode.php        # Image download strategies
│
└── Services/
    ├── Core/                         # Shared infrastructure
    │   ├── Abstracts/                # Base abstract classes
    │   ├── Contracts/                # Shared interfaces (ServiceInterface, FactoryInterface)
    │   ├── DTOs/                     # Shared data objects (FilterConfigurationData, etc.)
    │   ├── Exceptions/               # Custom exceptions (Filter, Reader, Downloader, etc.)
    │   ├── Operators/                # Operator definitions
    │   ├── Registry/                 # Service registries
    │   ├── Cache/                    # Caching strategies
    │   ├── Configuration/            # Configuration handling
    │   └── Traits/                   # HasOptions, ServiceTrait, etc.
    │
    ├── Downloader/                   # Data source downloaders
    │   ├── Abstracts/                # AbstractDownloader base
    │   ├── Contracts/                # DownloaderInterface
    │   ├── Factories/                # DownloaderFactory
    │   └── Implementations/
    │       ├── HttpDownloader.php    # HTTP/HTTPS downloads
    │       ├── FtpDownloader.php     # FTP downloads
    │       └── SftpDownloader.php    # SFTP downloads
    │
    ├── Reader/                       # Data format readers
    │   ├── Abstracts/                # AbstractReader base
    │   ├── Contracts/                # ReaderInterface
    │   ├── Factories/                # ReaderFactory
    │   └── Implementations/
    │       ├── CsvReader.php         # CSV parsing
    │       ├── JsonReader.php        # JSON parsing
    │       ├── XmlReader.php         # XML parsing
    │       └── YamlReader.php        # YAML parsing
    │
    ├── Filter/                       # Data filtering engine
    │   ├── Abstracts/                # AbstractFilterOperator (Template Method)
    │   ├── Contracts/                # FilterInterface, OperatorRegistryInterface, etc.
    │   ├── Extractors/               # DotNotationValueExtractor
    │   ├── Registry/                 # OperatorRegistry
    │   ├── Validators/               # FilterValidator
    │   └── Implementations/          # 17 filter operators
    │
    ├── Pipeline/                     # Pipeline orchestration
    │   ├── Contracts/                # PipelineExecutionServiceInterface, etc.
    │   ├── DTOs/                     # PipelineContext, StageResult, etc.
    │   ├── Factories/                # ImportPipelineConfigFactory
    │   ├── Orchestrators/            # Pipeline orchestrator
    │   ├── Pipes/                    # 7 sequential pipes (see below)
    │   ├── Services/                 # ExecutionService, SchedulingService, TestDataService
    │   ├── ValueObjects/             # Pipeline value objects
    │   └── Implementations/          # Concrete implementations
    │
    ├── Prepare/                      # Data preparation
    │   ├── Contracts/                # PrepareInterface
    │   └── Services/                 # Preparation services
    │
    ├── ImageDownloader/              # Image download handling
    │
    ├── ImportDashboard/              # Dashboard service & repository
    │   ├── ImportDashboardService.php
    │   └── ImportPipelineRepository.php
    │
    ├── Jobs/                         # Queue jobs (2)
    │
    └── Providers/                    # Sub-service providers (5)
        ├── DownloaderServiceProvider.php
        ├── ReaderServiceProvider.php
        ├── FilterServiceProvider.php
        ├── PrepareServiceProvider.php
        └── PipelineServiceProvider.php
```

---

## 🔄 Pipeline System

Each import pipeline consists of **7 sequential pipes** that process data in order:

```
┌─────────────┐    ┌──────────┐    ┌────────────┐    ┌──────────┐
│  1. Download │───▶│ 2. Read  │───▶│ 3. Filter  │───▶│  4. Map  │
└─────────────┘    └──────────┘    └────────────┘    └──────────┘
                                                          │
┌─────────────┐    ┌──────────┐    ┌────────────────┐     │
│   7. Save   │◀───│6. Prepare│◀───│5. Images Prep  │◀────┘
└─────────────┘    └──────────┘    └────────────────┘
```

### Pipes

| # | Pipe | File | Description |
|---|------|------|-------------|
| 1 | **DownloadPipe** | `Pipeline/Pipes/DownloadPipe.php` | Fetches raw data from HTTP, FTP, or SFTP sources |
| 2 | **ReadPipe** | `Pipeline/Pipes/ReadPipe.php` | Parses raw data using configured reader (CSV/JSON/XML/YAML) |
| 3 | **FilterPipe** | `Pipeline/Pipes/FilterPipe.php` | Applies filter rules with AND/OR logic to include/exclude rows |
| 4 | **MapPipe** | `Pipeline/Pipes/MapPipe.php` | Maps source fields → target fields via `elaitech/data-mapper` |
| 5 | **ImagesPreparePipe** | `Pipeline/Pipes/ImagesPreparePipe.php` | Processes image URLs — separators, index skipping, download modes |
| 6 | **PreparePipe** | `Pipeline/Pipes/PreparePipe.php` | Final prep — category resolution, VIN/stock ID generation |
| 7 | **SavePipe** | `Pipeline/Pipes/SavePipe.php` | Persists prepared rows by delegating to the class set in `import-pipelines.save.using` (must implement `ResultSaverInterface`); throws if unset |

### Pipeline Configuration Steps (Stepper)

Pipelines are configured through an **8-step stepper wizard**:

| Order | Step | Enum Value | Description |
|---|---|---|---|
| 1 | Basic Info | `basic-info` | Name, description, frequency, start time |
| 2 | Downloader Config | `downloader-config` | Protocol, URL, auth credentials |
| 3 | Reader Config | `reader-config` | Format, delimiter, encoding, root path |
| 4 | Filter Config | `filter-config` | Filter rules with operators and logic |
| 5 | Mapper Config | `mapper-config` | Field mappings with transformers |
| 6 | Images Prepare | `images-prepare-config` | Image handling settings |
| 7 | Prepare Config | `prepare-config` | Data preparation rules |
| 8 | Preview | `preview` | Review full configuration and test output |

---

## 📊 Models

### `ImportPipeline`

The core model representing an import pipeline definition.

| Field | Type | Description |
|---|---|---|
| `name` | `string` | Pipeline display name |
| `description` | `string` | Optional description |
| `target_id` | `string` | Target identifier |
| `is_active` | `boolean` | Whether the pipeline is enabled |
| `frequency` | `ImportPipelineFrequency` | `once`, `daily`, `weekly`, `monthly` |
| `start_time` | `datetime` | Scheduled start time |
| `last_executed_at` | `datetime` | Last execution timestamp |
| `next_execution_at` | `datetime` | Next scheduled execution |
| `created_by` / `updated_by` | `int` | Audit tracking (auto-set) |

**Relationships:** `config()`, `executions()`, `logs()`, `creator()`, `updater()`  
**Scopes:** `active()`, `scheduled()`  
**Accessors:** `status` (computed: active / inactive / needs_configuration)

### `ImportPipelineConfig`

Stores per-step JSON configuration. Each pipeline has multiple config entries (one per stepper step).

### `ImportPipelineExecution`

Tracks execution runs with status, timing, and result data.

| Field | Type | Description |
|---|---|---|
| `status` | `ImportPipelineStatus` | pending, running, completed, failed, cancelled |
| `started_at` / `completed_at` | `datetime` | Timing brackets |
| `total_rows` / `processed_rows` | `int` | Row counts |
| `success_rate` | `decimal` | Percentage success |
| `processing_time` | `decimal` | Execution time in seconds |
| `memory_usage` | `int` | Memory consumed |
| `error_message` | `string` | Failure details |
| `result_data` | `array` | Detailed result payload |

**Helper Methods:** `markAsRunning()`, `markAsCompleted()`, `markAsFailed()`, `markAsCancelled()`, `addLog()`

### `ImportPipelineLog`

Per-stage log entries with level, message, and context.

### `ImportPipelineTemplate`

Reusable pipeline configuration templates for quick setup.

---

## ⚙️ Services

### Downloader

| Implementation | Protocol | Key Features |
|---|---|---|
| `HttpDownloader` | HTTP/HTTPS | Headers, auth, SSL config, timeouts |
| `FtpDownloader` | FTP | Passive mode, directory listing |
| `SftpDownloader` | SFTP | Key-based auth, known hosts |

### Reader

| Implementation | Format | Key Features |
|---|---|---|
| `CsvReader` | CSV | Delimiter, enclosure, escape, encoding, header row |
| `JsonReader` | JSON | Root path, nested object traversal |
| `XmlReader` | XML | Node path, attribute mapping |
| `YamlReader` | YAML | Root key extraction |

### Filter (17 Operators)

Built using the **Template Method pattern** via `AbstractFilterOperator`:

| Category | Operators |
|---|---|
| **Equality** | `equals`, `not_equals` |
| **String** | `contains`, `not_contains`, `starts_with`, `ends_with` |
| **Numeric** | `greater_than`, `less_than`, `between`, `not_between` |
| **Set** | `in`, `not_in` |
| **Pattern** | `regex`, `not_regex` |
| **Null** | `is_null`, `is_not_null` |

**Features:**
- Dot-notation field access for nested data
- AND/OR logical grouping of rules
- Case-sensitive/insensitive matching
- Extensible via `AbstractFilterOperator` base class

### Pipeline Services

| Service | Description |
|---|---|
| `PipelineExecutionService` | Orchestrates full pipeline execution |
| `PipelineSchedulingService` | Handles scheduled pipeline runs |
| `PipelineTestDataService` | Provides test data for step testing |
| `ImportPipelineConfigFactory` | Creates/updates step configurations |

### Dashboard Services

| Service | Description |
|---|---|
| `ImportDashboardService` | Business logic for the dashboard UI |
| `ImportPipelineRepository` | Data access layer for pipelines |

---

## 📋 Enums

### `ImportPipelineStatus`
`pending` · `running` · `completed` · `failed` · `cancelled`

Methods: `getLabel()`, `getDescription()`, `getColor()`, `isActive()`, `isFinished()`, `isSuccessful()`, `isFailed()`, `isCancelled()`

### `ImportPipelineFrequency`
`once` · `daily` · `weekly` · `monthly`

Methods: `getLabel()`, `getDescription()`, `getOptions()`

### `ImportPipelineStep`
`basic-info` · `downloader-config` · `reader-config` · `filter-config` · `mapper-config` · `images-prepare-config` · `prepare-config` · `preview`

Methods: `title()`, `description()`, `route()`, `order()`

### `PipelineStage`
Represents the runtime execution stage of a pipeline.

### `PipelineStatus`
`active` · `inactive` · `needs_configuration`

### `ImageDownloadMode`
Strategies for handling image downloads during pipeline execution.

---

## ⚙️ Configuration

The package publishes `config/import-pipelines.php`:

```bash
php artisan vendor:publish --tag=import-config
```

---

## 🔧 Extending

### Adding a Custom Downloader

1. Create a class extending the abstract downloader
2. Implement the required methods
3. Register in `DownloaderServiceProvider`

### Adding a Custom Reader

1. Extend the abstract reader
2. Implement parsing logic
3. Register in `ReaderServiceProvider`

### Adding a Custom Filter Operator

```php
use Elaitech\Import\Services\Filter\Abstracts\AbstractFilterOperator;

final class CustomOperator extends AbstractFilterOperator
{
    public function getName(): string { return 'custom'; }
    public function getLabel(): string { return 'Custom Operator'; }
    public function getDescription(): string { return 'My custom filter logic'; }
    public function supportsValueType(mixed $value): bool { return true; }

    protected function doApply(mixed $dataValue, mixed $filterValue, array $options): bool
    {
        // Your logic here
        return $dataValue === $filterValue;
    }
}
```

Register it in `FilterServiceProvider`:

```php
$registry->register(new CustomOperator());
```

### Implementing Persistence (`ResultSaverInterface`)

`SavePipe` does not persist anything itself — it resolves the class named by
`config('import-pipelines.save.using')` and calls it. Point that config at your
own saver:

```php
// config/import-pipelines.php
'save' => ['using' => \App\Import\ProductResultSaver::class],

// App\Import\ProductResultSaver
use Elaitech\Import\Services\Core\Contracts\ResultSaverInterface;
use Elaitech\Import\Services\Pipeline\DTOs\PipelinePassable;
use Elaitech\Import\Services\Pipeline\DTOs\SaveResultData;

final class ProductResultSaver implements ResultSaverInterface
{
    public function save(PipelinePassable $passable, string|int $targetId): SaveResultData
    {
        $rows = $passable->prepareResult->preparedData; // final mapped rows
        // create/update your models, then return counts:
        return new SaveResultData(createdCount: /* … */, updatedCount: /* … */, totalProcessed: count($rows));
    }
}
```

Category resolution and value normalization belong upstream: use a Prepare-step
resolver (`config('import-pipelines.prepare.using')`, a `ResolverInterface`) and
the mapper's `value_mapping`, respectively — keep the saver dumb.

---

## 🧪 Testing

```bash
# From the package directory
./vendor/bin/phpunit

# From the root project
php artisan test
```

---

## 📦 Dependencies

| Package | Version | Purpose |
|---|---|---|
| `illuminate/support` | ^12.0 | Laravel framework support |
| `illuminate/database` | ^12.0 | Eloquent ORM |
| `illuminate/http` | ^12.0 | HTTP handling |
| `illuminate/queue` | ^12.0 | Queue jobs |
| `illuminate/console` | ^12.0 | Artisan commands |
| `spatie/laravel-activitylog` | ^4.11 | Audit logging |
| `spatie/laravel-data` | ^4.19 | Typed DTOs |
| `spatie/laravel-view-models` | ^1.6 | View models |
| `league/flysystem-ftp` | ^3.31 | FTP filesystem |
| `league/flysystem-sftp-v3` | ^3.31 | SFTP filesystem |
| `symfony/yaml` | ^7.4 | YAML parsing |

---

## 📄 License

MIT — see [LICENSE](LICENSE) for details.
