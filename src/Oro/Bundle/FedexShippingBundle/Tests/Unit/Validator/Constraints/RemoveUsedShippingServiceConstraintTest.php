<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\FedexShippingBundle\Validator\Constraints\RemoveUsedShippingServiceConstraint;
use Symfony\Component\Validator\Constraint;

class RemoveUsedShippingServiceConstraintTest extends \PHPUnit\Framework\TestCase
{
    public function testGetTargets()
    {
        $constraint = new RemoveUsedShippingServiceConstraint();
        self::assertSame(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }
}
