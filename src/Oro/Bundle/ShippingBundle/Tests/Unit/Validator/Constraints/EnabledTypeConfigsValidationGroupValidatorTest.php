<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Validator\Constraints\EnabledTypeConfigsValidationGroup;
use Oro\Bundle\ShippingBundle\Validator\Constraints\EnabledTypeConfigsValidationGroupValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class EnabledTypeConfigsValidationGroupValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): EnabledTypeConfigsValidationGroupValidator
    {
        return new EnabledTypeConfigsValidationGroupValidator();
    }

    public function testValidateWithoutDuplications()
    {
        $data = new ArrayCollection([
            (new ShippingMethodTypeConfig())->setEnabled(false),
            (new ShippingMethodTypeConfig())->setEnabled(true),
        ]);

        $constraint = new EnabledTypeConfigsValidationGroup();
        $this->validator->validate($data, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithDuplications()
    {
        $data = new ArrayCollection([
            (new ShippingMethodTypeConfig())->setEnabled(false),
            (new ShippingMethodTypeConfig())->setEnabled(false),
        ]);

        $constraint = new EnabledTypeConfigsValidationGroup();
        $this->validator->validate($data, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameters(['{{ count }}' => 0, '{{ limit }}' => 1])
            ->atPath('property.path.configurations')
            ->setPlural($constraint->min)
            ->assertRaised();
    }

    public function testUnexpectedValue()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "array or Traversable and Countable", "string" given'
        );

        $constraint = new EnabledTypeConfigsValidationGroup();
        $this->validator->validate('test', $constraint);
    }

    public function testUnexpectedItem()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig",'
            . ' "stdClass" given'
        );

        $constraint = new EnabledTypeConfigsValidationGroup();
        $this->validator->validate(new ArrayCollection([new \stdClass()]), $constraint);
    }
}
