# Filter System - Refactored Structure

This document outlines the refactored filter system that follows the same structure as downloaders and readers, with improved code organization and reduced duplication.

## 🏗️ Directory Structure

The filter system now follows the same pattern as downloaders and readers:

```
Filter/
├── Abstracts/           # Abstract base classes
│   └── AbstractFilterOperator.php
├── Contracts/           # Interface definitions
│   ├── FilterInterface.php
│   ├── OperatorRegistryInterface.php
│   ├── ValueExtractorInterface.php
│   └── FilterValidatorInterface.php
├── DTOs/               # Data Transfer Objects (shared with Core)
├── Extractors/         # Value extraction strategies
│   └── DotNotationValueExtractor.php
├── Implementations/    # Concrete implementations
│   ├── DataFilterService.php
│   ├── EqualsOperator.php
│   ├── ContainsOperator.php
│   ├── RegexOperator.php
│   ├── GreaterThanOperator.php
│   ├── InOperator.php
│   └── ... (other operators)
├── Registry/           # Service registries
│   └── OperatorRegistry.php
├── UI/                 # UI components
├── Validators/         # Validation logic
│   └── FilterValidator.php
└── README.md          # This file
```

## 🔧 Key Improvements

### 1. **Eliminated Code Duplication**

#### Before (Duplicated in every operator):
```php
public function apply(mixed $dataValue, mixed $filterValue, array $options = []): bool
{
    $dataValue = $this->normalizeValue($dataValue);
    $filterValue = $this->normalizeValue($filterValue);

    if ($this->isNullValue($dataValue) || $this->isNullValue($filterValue)) {
        return false;
    }

    $this->validateValueType($dataValue);
    
    // Operator-specific logic here...
}
```

#### After (Centralized in abstract class):
```php
// In AbstractFilterOperator
public function apply(mixed $dataValue, mixed $filterValue, array $options = []): bool
{
    $dataValue = $this->normalizeValue($dataValue);
    $filterValue = $this->normalizeValue($filterValue);

    if ($this->isNullValue($dataValue) || $this->isNullValue($filterValue)) {
        return $this->handleNullValues($dataValue, $filterValue);
    }

    $this->validateValueType($dataValue);

    return $this->doApply($dataValue, $filterValue, $options);
}

// In concrete operators
protected function doApply(mixed $dataValue, mixed $filterValue, array $options): bool
{
    // Only operator-specific logic here
}
```

### 2. **Consistent Structure with Downloaders/Readers**

The filter system now follows the exact same pattern:

```
Downloader/          Reader/          Filter/
├── Abstracts/       ├── Abstracts/   ├── Abstracts/
├── Contracts/       ├── Contracts/   ├── Contracts/
├── Implementations/ ├── Implementations/ ├── Implementations/
├── Factories/       ├── Factories/   ├── Registry/
├── UI/              ├── UI/          ├── UI/
└── Providers/       └── Providers/   └── Providers/
```

### 3. **Template Method Pattern**

The `AbstractFilterOperator` implements the Template Method pattern:

1. **Common Steps** (in abstract class):
   - Normalize values
   - Handle null values
   - Validate value types
   - Call concrete implementation

2. **Variable Steps** (in concrete classes):
   - Implement `doApply()` method
   - Define operator-specific logic

### 4. **Improved Maintainability**

- **Single Source of Truth**: Common logic in one place
- **Easy to Extend**: Add new operators by implementing `doApply()`
- **Consistent Behavior**: All operators handle edge cases the same way
- **Better Testing**: Test common logic once, focus on operator-specific logic

## 🚀 Usage Examples

### Creating a New Operator

```php
<?php

declare(strict_types=1);

namespace App\Services\Import\Filter\Implementations;

use App\Services\Import\Filter\Abstracts\AbstractFilterOperator;

final class CustomOperator extends AbstractFilterOperator
{
    public function getName(): string
    {
        return 'custom_operator';
    }

    public function getLabel(): string
    {
        return 'Custom Operator';
    }

    public function getDescription(): string
    {
        return 'Custom filter operator with specific logic';
    }

    public function supportsValueType(mixed $value): bool
    {
        return $this->isStringValue($value);
    }

    // Only implement the specific logic - common handling is automatic
    protected function doApply(mixed $dataValue, mixed $filterValue, array $options): bool
    {
        // Your custom logic here
        return $this->performCustomLogic($dataValue, $filterValue, $options);
    }

    private function performCustomLogic(string $dataValue, string $filterValue, array $options): bool
    {
        // Implementation specific to your needs
        return true;
    }
}
```

### Using the Filter Service

```php
use App\Services\Import\Filter\Implementations\DataFilterService;
use App\Services\Import\Core\DTOs\FilterConfigurationData;
use App\Services\Import\Core\DTOs\FilterRuleData;

$filterService = app(DataFilterService::class);

$data = [
    ['name' => 'John Doe', 'age' => 30, 'email' => 'john@example.com'],
    ['name' => 'Jane Smith', 'age' => 25, 'email' => 'jane@example.com'],
];

$rules = [
    new FilterRuleData('age', 'greater_than', 25),
    new FilterRuleData('email', 'contains', 'example'),
];

$config = new FilterConfigurationData($data, $rules, 'AND');
$result = $filterService->filter($config);
```

## 🧪 Testing

### Unit Testing Operators

```php
public function test_equals_operator(): void
{
    $operator = new EqualsOperator();
    
    // Test with normalized values
    $this->assertTrue($operator->apply('John', 'John', ['case_sensitive' => true]));
    $this->assertFalse($operator->apply('John', 'Jane', ['case_sensitive' => true]));
    
    // Test case insensitive
    $this->assertTrue($operator->apply('John', 'john', ['case_sensitive' => false]));
    
    // Test null handling (automatic)
    $this->assertFalse($operator->apply(null, 'John', []));
    $this->assertFalse($operator->apply('John', null, []));
}
```

### Integration Testing

```php
public function test_filter_service_integration(): void
{
    $filterService = app(DataFilterService::class);
    
    $data = [/* test data */];
    $rules = [/* test rules */];
    $config = new FilterConfigurationData($data, $rules);
    
    $result = $filterService->filter($config);
    
    $this->assertInstanceOf(FilterResultData::class, $result);
    $this->assertGreaterThan(0, $result->filteredRows);
}
```

## 📊 Performance Benefits

### 1. **Reduced Code Duplication**
- **Before**: ~15 lines of common code per operator × 14 operators = 210 lines
- **After**: 15 lines in abstract class + 5-10 lines per operator = ~155 lines
- **Savings**: ~55 lines of duplicated code eliminated

### 2. **Consistent Behavior**
- All operators handle edge cases identically
- Predictable behavior across all operators
- Easier debugging and maintenance

### 3. **Better Memory Usage**
- Common logic shared through inheritance
- No duplicate method calls
- More efficient object creation

## 🔧 Configuration

### Service Provider Registration

```php
// In FilterServiceProvider
private function registerBuiltInOperators(OperatorRegistryInterface $registry): void
{
    $operators = [
        new EqualsOperator(),
        new ContainsOperator(),
        new RegexOperator(),
        // ... other operators
    ];

    foreach ($operators as $operator) {
        $registry->register($operator);
    }
}
```

### Adding Custom Operators

1. Create operator class in `Implementations/`
2. Extend `AbstractFilterOperator`
3. Implement `doApply()` method
4. Register in `FilterServiceProvider`
5. Add tests

## 🎯 Best Practices

### 1. **Operator Implementation**
- Always extend `AbstractFilterOperator`
- Only implement `doApply()` for specific logic
- Use helper methods from abstract class
- Handle edge cases in `doApply()` if needed

### 2. **Testing**
- Test common behavior through abstract class tests
- Focus operator tests on specific logic
- Use integration tests for full workflow

### 3. **Documentation**
- Document operator-specific behavior
- Include usage examples
- Update README when adding operators

### 4. **Error Handling**
- Let abstract class handle common errors
- Add operator-specific error handling in `doApply()`
- Use meaningful error messages

## 🔄 Migration from Old Structure

### Before (Old Structure)
```php
// Duplicated in every operator
$dataValue = $this->normalizeValue($dataValue);
$filterValue = $this->normalizeValue($filterValue);
// ... common logic
```

### After (New Structure)
```php
// In AbstractFilterOperator
public function apply(mixed $dataValue, mixed $filterValue, array $options = []): bool
{
    // Common logic here
    return $this->doApply($dataValue, $filterValue, $options);
}

// In concrete operators
protected function doApply(mixed $dataValue, mixed $filterValue, array $options): bool
{
    // Only specific logic
}
```

## 📈 Future Enhancements

1. **Caching**: Add operator result caching
2. **Metrics**: Enhanced performance monitoring
3. **Validation**: More sophisticated validation rules
4. **Extractors**: Additional value extraction strategies
5. **UI**: Enhanced test interface

This refactored structure provides a solid foundation for the filter system that is maintainable, testable, and follows established patterns in the codebase.