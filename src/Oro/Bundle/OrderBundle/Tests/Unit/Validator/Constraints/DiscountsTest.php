<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\OrderBundle\Validator\Constraints\Discounts;
use Oro\Bundle\OrderBundle\Validator\Constraints\DiscountsValidator;

class DiscountsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Discounts
     */
    protected $constraint;

    protected function setUp(): void
    {
        $this->constraint = new Discounts();
    }

    public function testGetTargets()
    {
        $this->assertEquals(['class', 'property'], $this->constraint->getTargets());
    }

    public function testValidatedBy()
    {
        $this->assertEquals(DiscountsValidator::class, $this->constraint->validatedBy());
    }
}
