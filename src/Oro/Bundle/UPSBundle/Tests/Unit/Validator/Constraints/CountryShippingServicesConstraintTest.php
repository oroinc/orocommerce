<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\UPSBundle\Validator\Constraints\CountryShippingServicesConstraint;
use Symfony\Component\Validator\Constraint;

class CountryShippingServicesConstraintTest extends \PHPUnit\Framework\TestCase
{
    public function testValidatedBy()
    {
        $constraint = new CountryShippingServicesConstraint();

        static::assertSame('oro_ups_country_shipping_services_validator', $constraint->validatedBy());
    }

    public function testGetTargets()
    {
        $constraint = new CountryShippingServicesConstraint();

        static::assertSame(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }
}
