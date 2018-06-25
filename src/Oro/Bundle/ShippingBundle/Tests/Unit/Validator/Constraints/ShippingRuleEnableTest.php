<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ShippingBundle\Validator\Constraints\ShippingRuleEnable;
use Oro\Bundle\ShippingBundle\Validator\Constraints\ShippingRuleEnableValidator;

class ShippingRuleEnableTest extends \PHPUnit\Framework\TestCase
{
    public function testValidatedBy()
    {
        $constraint = new ShippingRuleEnable();

        static::assertSame(ShippingRuleEnableValidator::class, $constraint->validatedBy());
    }
}
