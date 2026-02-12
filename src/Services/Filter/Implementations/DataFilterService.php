<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Filter\Implementations;

use Elaitech\Import\Services\Core\DTOs\FilterConfigurationData;
use Elaitech\Import\Services\Core\DTOs\FilterResultData;
use Elaitech\Import\Services\Core\DTOs\FilterRuleData;
use Elaitech\Import\Services\Filter\Contracts\FilterInterface;
use Elaitech\Import\Services\Filter\Contracts\FilterValidatorInterface;
use Elaitech\Import\Services\Filter\Contracts\OperatorRegistryInterface;
use Elaitech\Import\Services\Filter\Contracts\ValueExtractorInterface;
use Psr\Log\LoggerInterface;

final readonly class DataFilterService implements FilterInterface
{
    public function __construct(
        private OperatorRegistryInterface $operatorRegistry,
        private ValueExtractorInterface $valueExtractor,
        private FilterValidatorInterface $validator,
        private LoggerInterface $logger
    ) {}

    public function filter(FilterConfigurationData $config): FilterResultData
    {
        $this->logger->info('Starting data filter operation', [
            'total_rows' => count($config->data),
            'filter_rules_count' => $config->getRuleCount(),
        ]);

        if (! $config->hasRules()) {
            return $this->createNoFilterResult($config->data);
        }

        $result = $this->processFiltering($config);

        $this->logger->info('Data filter operation completed', [
            'total_rows' => $result->totalRows,
            'filtered_rows' => $result->filteredRows,
            'excluded_rows' => $result->excludedRows,
            'filter_efficiency' => $result->getFilterEfficiency(),
        ]);

        return $result;
    }

    public function getAvailableOperators(): array
    {
        return $this->operatorRegistry->getMetadata();
    }

    public function validateRule(array $rule): array
    {
        return $this->validator->validateRule($rule);
    }

    private function createNoFilterResult(array $data): FilterResultData
    {
        return new FilterResultData(
            filteredData: $data,
            originalData: [],
            totalRows: count($data),
            filteredRows: count($data),
            excludedRows: 0,
        );
    }

    private function processFiltering(FilterConfigurationData $config): FilterResultData
    {
        $filteredData = [];
        $errors = [];

        foreach ($config->data as $rowIndex => $row) {
            try {
                $shouldInclude = $this->evaluateRow($row, $config->filterRules);

                if ($shouldInclude) {
                    $filteredData[] = $row;
                }
            } catch (\Throwable $e) {
                $errorMessage = "Row {$rowIndex}: ".$e->getMessage();
                $errors[] = $errorMessage;
                $this->logger->warning('Filter evaluation failed for row', [
                    'row_index' => $rowIndex,
                    'error' => $e->getMessage(),
                ]);
            } finally {
                unset($config->data[$rowIndex]);
            }
        }

        return $this->createFilterResult($config, $filteredData, $errors);
    }

    private function evaluateRow(array $row, array $filterRules): bool
    {

        foreach ($filterRules as $rule) {
            $value = $this->valueExtractor->extract($row, $rule->key);
            $options = $this->buildOperatorOptions($rule);
            $operator = $this->operatorRegistry->get($rule->operator);

            if (! $operator->apply($value, $rule->value, $options)) {
                return false;
            }
        }

        return true;
    }

    private function buildOperatorOptions(FilterRuleData $rule): array
    {
        return [
            'case_sensitive' => $rule->caseSensitive,
            'regex_flags' => $rule->regexFlags,
        ];
    }

    private function createFilterResult(FilterConfigurationData $config, array $filteredData, array $errors): FilterResultData
    {
        $totalRows = count($config->data);
        $filteredRows = count($filteredData);
        $excludedRows = $totalRows - $filteredRows;
        $filterStats = $this->calculateFilterStats($config->filterRules);

        return new FilterResultData(
            filteredData: $filteredData,
            originalData: [],
            totalRows: $totalRows,
            filteredRows: $filteredRows,
            excludedRows: $excludedRows,
            filterStats: $filterStats,
            errors: $errors,
        );
    }

    private function calculateFilterStats(array $filterRules): array
    {
        return array_map(
            fn (FilterRuleData $rule) => [
                'key' => $rule->key,
                'operator' => $rule->operator,
                'value' => $rule->value,
                'description' => $rule->getDescription(),
            ],
            $filterRules
        );
    }
}
