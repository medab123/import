<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\Implementations;

use Elaitech\Import\Enums\PipelineStage;
use Elaitech\Import\Services\Downloader\Contracts\DownloaderFactoryInterface;
use Elaitech\Import\Services\Filter\Contracts\FilterInterface;
use Elaitech\Import\Services\Pipeline\Contracts\ImportPipelineInterface;
use Elaitech\Import\Services\Pipeline\DTOs\ImportPipelineConfig;
use Elaitech\Import\Services\Pipeline\DTOs\ImportPipelineResult;
use Elaitech\Import\Services\Pipeline\Orchestrators\PipelineOrchestrator;
use Elaitech\Import\Services\Reader\Contracts\ReaderFactoryInterface;

final readonly class ImportPipelineService implements ImportPipelineInterface
{
    public function __construct(
        private PipelineOrchestrator $orchestrator,
        private DownloaderFactoryInterface $downloaderFactory,
        private ReaderFactoryInterface $readerFactory,
        private FilterInterface $filterService,
    ) {}

    public function process(ImportPipelineConfig $config): ImportPipelineResult
    {
        return $this->orchestrator->executeAll($config);
    }

    public function executeToStage(ImportPipelineConfig $config, PipelineStage $stage): ImportPipelineResult
    {
        return $this->orchestrator->execute($config, $stage);
    }

    public function getAvailableDownloader(): array
    {
        return $this->downloaderFactory->getAvailableTypes();
    }

    public function getAvailableReaders(): array
    {
        return $this->readerFactory->getAvailableTypes();
    }

    public function getAvailableFilterOperators(): array
    {
        return $this->filterService->getAvailableOperators();
    }

    public function validateConfig(ImportPipelineConfig $config): array
    {
        $errors = [];

        // Validate downloader
        if (! in_array(parse_url($config->downloadRequest->source, PHP_URL_SCHEME), $this->getAvailableDownloader())) {
            $errors[] = 'Unsupported downloader scheme';
        }

        // Validate reader
        if (! in_array($config->readerConfig->type, $this->getAvailableReaders())) {
            $errors[] = 'Unsupported reader type';
        }

        // Validate filter rules if provided
        if ($config->filterConfig) {
            foreach ($config->filterConfig->filterRules as $rule) {
                $ruleErrors = $this->filterService->validateRule([
                    'key' => $rule->key,
                    'operator' => $rule->operator,
                    'value' => $rule->value,
                ]);

                if (! empty($ruleErrors)) {
                    $errors = array_merge($errors, $ruleErrors);
                }
            }
        }

        return $errors;
    }
}
