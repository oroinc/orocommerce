<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ConsentBundle\Validator\Constraints\RequiredConsents;

class RequiredConsentsTest extends \PHPUnit\Framework\TestCase
{
    public function testValidatedBy()
    {
        $constraint = new RequiredConsents();

        $this->assertEquals('oro_consent.validator.required_consents', $constraint->validatedBy());
    }
}
