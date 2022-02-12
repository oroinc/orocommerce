<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\NumericRange;

class NumericRangeTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructWithoutOptions(): void
    {
        $constraint = new NumericRange();
        self::assertEquals(0, $constraint->min);
        self::assertEquals('999999999999999.9999', $constraint->max);
    }

    public function testConstructWithOptions(): void
    {
        $constraint = new NumericRange(['precision' => 5, 'scale' => 2]);
        self::assertEquals(0, $constraint->min);
        self::assertEquals('999.99', $constraint->max);

        $constraint = new NumericRange(['min' => 1, 'max' => PHP_INT_MAX]);
        self::assertEquals(1, $constraint->min);
        self::assertEquals(PHP_INT_MAX, $constraint->max);
    }
}
