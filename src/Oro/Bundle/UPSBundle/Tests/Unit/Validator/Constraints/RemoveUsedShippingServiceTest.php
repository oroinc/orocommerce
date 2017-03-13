<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\UPSBundle\Validator\Constraints\RemoveUsedShippingService;
use Oro\Bundle\UPSBundle\Validator\Constraints\RemoveUsedShippingServiceValidator;
use Symfony\Component\Validator\Constraint;

class RemoveUsedShippingServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testValidatedBy()
    {
        $constraint = new RemoveUsedShippingService();

        static::assertSame(RemoveUsedShippingServiceValidator::ALIAS, $constraint->validatedBy());
    }

    public function testGetTargets()
    {
        $constraint = new RemoveUsedShippingService();

        static::assertSame(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }
}
