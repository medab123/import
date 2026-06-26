<?php

declare(strict_types=1);

namespace Elaitech\Import\Tests\Unit;

use Elaitech\Import\Services\Filter\Implementations\InOperator;
use Elaitech\Import\Services\Filter\Implementations\IsNotNullOperator;
use Elaitech\Import\Services\Filter\Implementations\IsNullOperator;
use Elaitech\Import\Services\Filter\Implementations\NotBetweenOperator;
use Elaitech\Import\Services\Filter\Implementations\NotContainsOperator;
use Elaitech\Import\Services\Filter\Implementations\NotInOperator;
use PHPUnit\Framework\TestCase;

class FilterOperatorsTest extends TestCase
{
    public function test_is_null_matches_empty_and_rejects_populated(): void
    {
        $op = new IsNullOperator;

        $this->assertTrue($op->apply(null, null));
        $this->assertTrue($op->apply('', null));
        $this->assertFalse($op->apply('value', null));
    }

    public function test_is_not_null_is_the_inverse(): void
    {
        $op = new IsNotNullOperator;

        $this->assertTrue($op->apply('value', null));
        $this->assertFalse($op->apply(null, null));
        $this->assertFalse($op->apply('', null));
    }

    public function test_not_in_includes_null_data(): void
    {
        $op = new NotInOperator;

        $this->assertTrue($op->apply(null, ['a', 'b']));   // null is not in the list
        $this->assertTrue($op->apply('c', ['a', 'b']));
        $this->assertFalse($op->apply('a', ['a', 'b']));
    }

    public function test_not_contains_and_not_between_include_null_data(): void
    {
        $this->assertTrue((new NotContainsOperator)->apply(null, 'foo'));
        $this->assertTrue((new NotBetweenOperator)->apply(null, [1, 10]));
    }

    public function test_in_matches_across_string_and_number_types(): void
    {
        $op = new InOperator;

        $this->assertTrue($op->apply('5', [5, 6]));   // "5" from CSV vs 5 in list
        $this->assertTrue($op->apply(5, ['5', '6']));
        $this->assertFalse($op->apply('7', [5, 6]));
    }
}
