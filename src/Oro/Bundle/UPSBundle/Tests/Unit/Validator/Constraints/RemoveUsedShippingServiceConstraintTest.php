<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\UPSBundle\Validator\Constraints\RemoveUsedShippingServiceConstraint;
use Symfony\Component\Validator\Constraint;

class RemoveUsedShippingServiceConstraintTest extends \PHPUnit\Framework\TestCase
{
    public function testValidatedBy()
    {
        $constraint = new RemoveUsedShippingServiceConstraint();

        static::assertSame('oro_ups_remove_used_shipping_service_validator', $constraint->validatedBy());
    }

    public function testGetTargets()
    {
        $constraint = new RemoveUsedShippingServiceConstraint();

        static::assertSame(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }
}
