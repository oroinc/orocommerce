<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\FedexShippingBundle\Validator\Constraints\RemoveUsedShippingServiceConstraint;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;

class RemoveUsedShippingServiceConstraintTest extends TestCase
{
    public function testGetTargets()
    {
        static::assertSame(
            Constraint::CLASS_CONSTRAINT,
            (new RemoveUsedShippingServiceConstraint())->getTargets()
        );
    }

    public function testValidatedBy()
    {
        static::assertSame(
            'oro_fedex_shipping_remove_used_shipping_service_validator',
            (new RemoveUsedShippingServiceConstraint())->validatedBy()
        );
    }
}
