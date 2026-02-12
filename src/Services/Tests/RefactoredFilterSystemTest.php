<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Tests;

use Elaitech\Import\Services\Core\DTOs\FilterConfigurationData;
use Elaitech\Import\Services\Core\DTOs\FilterRuleData;
use Elaitech\Import\Services\Filter\Contracts\FilterValidatorInterface;
use Elaitech\Import\Services\Filter\Contracts\OperatorRegistryInterface;
use Elaitech\Import\Services\Filter\Contracts\ValueExtractorInterface;
use Elaitech\Import\Services\Filter\Extractors\DotNotationValueExtractor;
use Elaitech\Import\Services\Filter\Implementations\ContainsOperator;
use Elaitech\Import\Services\Filter\Implementations\DataFilterService;
use Elaitech\Import\Services\Filter\Implementations\EqualsOperator;
use Elaitech\Import\Services\Filter\Implementations\RegexOperator;
use Elaitech\Import\Services\Filter\Registry\OperatorRegistry;
use Elaitech\Import\Services\Filter\Validators\FilterValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class RefactoredFilterSystemTest extends TestCase
{
    use RefreshDatabase;

    private DataFilterService $filterService;

    private OperatorRegistryInterface $operatorRegistry;

    private ValueExtractorInterface $valueExtractor;

    private FilterValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operatorRegistry = new OperatorRegistry;
        $this->valueExtractor = new DotNotationValueExtractor;
        $this->validator = new FilterValidator($this->operatorRegistry);

        $this->filterService = new DataFilterService(
            $this->operatorRegistry,
            $this->valueExtractor,
            $this->validator,
            $this->createMock(LoggerInterface::class)
        );

        $this->registerTestOperators();
    }

    private function registerTestOperators(): void
    {
        $this->operatorRegistry->register(new EqualsOperator);
        $this->operatorRegistry->register(new ContainsOperator);
        $this->operatorRegistry->register(new RegexOperator);
    }

    public function test_dependency_injection_works(): void
    {
        $this->assertInstanceOf(DataFilterService::class, $this->filterService);
        $this->assertInstanceOf(OperatorRegistryInterface::class, $this->operatorRegistry);
        $this->assertInstanceOf(ValueExtractorInterface::class, $this->valueExtractor);
        $this->assertInstanceOf(FilterValidatorInterface::class, $this->validator);
    }

    public function test_operator_registry_functionality(): void
    {
        $this->assertTrue($this->operatorRegistry->has('equals'));
        $this->assertTrue($this->operatorRegistry->has('contains'));
        $this->assertFalse($this->operatorRegistry->has('nonexistent'));

        $operator = $this->operatorRegistry->get('equals');
        $this->assertInstanceOf(EqualsOperator::class, $operator);

        $metadata = $this->operatorRegistry->getMetadata();
        $this->assertArrayHasKey('equals', $metadata);
        $this->assertArrayHasKey('contains', $metadata);
    }

    public function test_value_extractor_dot_notation(): void
    {
        $data = [
            'user' => [
                'name' => 'John',
                'profile' => [
                    'age' => 30,
                    'email' => 'john@example.com',
                ],
            ],
        ];

        $this->assertEquals('John', $this->valueExtractor->extract($data, 'user.name'));
        $this->assertEquals(30, $this->valueExtractor->extract($data, 'user.profile.age'));
        $this->assertEquals('john@example.com', $this->valueExtractor->extract($data, 'user.profile.email'));
        $this->assertNull($this->valueExtractor->extract($data, 'user.nonexistent'));
        $this->assertNull($this->valueExtractor->extract($data, 'user.profile.nonexistent'));
    }

    public function test_filter_validator(): void
    {
        $validRule = ['key' => 'name', 'operator' => 'equals', 'value' => 'John'];
        $this->assertTrue($this->validator->isValid($validRule));
        $this->assertEmpty($this->validator->validateRule($validRule));

        $invalidRule = ['key' => '', 'operator' => 'nonexistent', 'value' => null];
        $this->assertFalse($this->validator->isValid($invalidRule));
        $errors = $this->validator->validateRule($invalidRule);
        $this->assertNotEmpty($errors);
        $this->assertContains('Field \'key\' is required', $errors);
        $this->assertContains('Unknown operator: nonexistent', $errors);
    }

    public function test_refactored_filtering(): void
    {
        $data = [
            ['name' => 'John Doe', 'age' => 30, 'email' => 'john@example.com'],
            ['name' => 'Jane Smith', 'age' => 25, 'email' => 'jane@example.com'],
            ['name' => 'Bob Johnson', 'age' => 35, 'email' => 'bob@test.com'],
        ];

        $rules = [
            new FilterRuleData('age', 'equals', 30),
        ];

        $config = new FilterConfigurationData($data, $rules);
        $result = $this->filterService->filter($config);

        $this->assertCount(1, $result->filteredData);
        $this->assertEquals('John Doe', $result->filteredData[0]['name']);
        $this->assertEquals(3, $result->totalRows);
        $this->assertEquals(1, $result->filteredRows);
        $this->assertEquals(2, $result->excludedRows);
    }

    public function test_contains_operator_refactored(): void
    {
        $data = [
            ['name' => 'John Doe', 'email' => 'john@example.com'],
            ['name' => 'Jane Smith', 'email' => 'jane@test.com'],
            ['name' => 'Bob Johnson', 'email' => 'bob@example.org'],
        ];

        $rules = [
            new FilterRuleData('email', 'contains', 'example'),
        ];

        $config = new FilterConfigurationData($data, $rules);
        $result = $this->filterService->filter($config);

        $this->assertCount(2, $result->filteredData);
        $this->assertStringContainsString('example', $result->filteredData[0]['email']);
        $this->assertStringContainsString('example', $result->filteredData[1]['email']);
    }

    public function test_regex_operator_refactored(): void
    {
        $data = [
            ['name' => 'John Doe', 'email' => 'john@example.com'],
            ['name' => 'Jane Smith', 'email' => 'jane@test.com'],
            ['name' => 'Bob Johnson', 'email' => 'bob@example.org'],
        ];

        $rules = [
            new FilterRuleData('email', 'regex', '@example\.(com|org)$'),
        ];

        $config = new FilterConfigurationData($data, $rules);
        $result = $this->filterService->filter($config);

        $this->assertCount(2, $result->filteredData);
    }

    public function test_error_handling(): void
    {
        $data = [
            ['name' => 'John', 'age' => 30],
            ['invalid' => 'data'], // Missing required fields
        ];

        $rules = [
            new FilterRuleData('age', 'equals', 30),
        ];

        $config = new FilterConfigurationData($data, $rules);
        $result = $this->filterService->filter($config);

        // Should handle missing fields gracefully
        $this->assertCount(1, $result->filteredData);
        $this->assertEquals('John', $result->filteredData[0]['name']);
    }

    public function test_performance_with_large_dataset(): void
    {
        $data = [];
        for ($i = 0; $i < 1000; $i++) {
            $data[] = [
                'id' => $i,
                'name' => "User {$i}",
                'age' => 20 + ($i % 50),
                'active' => $i % 2 === 0,
            ];
        }

        $rules = [
            new FilterRuleData('age', 'equals', 30),
            new FilterRuleData('active', 'equals', true),
        ];

        $config = new FilterConfigurationData($data, $rules, 'AND');

        $startTime = microtime(true);
        $result = $this->filterService->filter($config);
        $endTime = microtime(true);

        $executionTime = $endTime - $startTime;

        // Should complete within reasonable time (less than 1 second)
        $this->assertLessThan(1.0, $executionTime);
        $this->assertGreaterThan(0, $result->filteredRows);

        // Log performance metrics
        $this->assertGreaterThan(0, $result->getFilterEfficiency());
    }

    public function test_get_available_operators(): void
    {
        $operators = $this->filterService->getAvailableOperators();

        $this->assertIsArray($operators);
        $this->assertArrayHasKey('equals', $operators);
        $this->assertArrayHasKey('contains', $operators);
        $this->assertArrayHasKey('regex', $operators);

        // Check operator metadata structure
        $equalsOperator = $operators['equals'];
        $this->assertArrayHasKey('name', $equalsOperator);
        $this->assertArrayHasKey('label', $equalsOperator);
        $this->assertArrayHasKey('description', $equalsOperator);
        $this->assertArrayHasKey('expected_value_type', $equalsOperator);
        $this->assertArrayHasKey('validation_rules', $equalsOperator);
    }
}
