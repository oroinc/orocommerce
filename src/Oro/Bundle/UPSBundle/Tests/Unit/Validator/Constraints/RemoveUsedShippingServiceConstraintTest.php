<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\UPSBundle\Validator\Constraints\RemoveUsedShippingServiceConstraint;
use Symfony\Component\Validator\Constraint;

class RemoveUsedShippingServiceConstraintTest extends \PHPUnit\Framework\TestCase
{
    public function testGetTargets()
    {
        $constraint = new RemoveUsedShippingServiceConstraint();
        self::assertSame(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }
}
