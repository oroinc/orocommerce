<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Validator\Constraints\UniqueProductUnitShippingOptions;
use Oro\Bundle\ShippingBundle\Validator\Constraints\UniqueProductUnitShippingOptionsValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UniqueProductUnitShippingOptionsValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): UniqueProductUnitShippingOptionsValidator
    {
        return new UniqueProductUnitShippingOptionsValidator();
    }

    public function testValidateWithoutDuplications()
    {
        $value = new ArrayCollection([
            $this->createProductShippingOptions('lbs'),
            $this->createProductShippingOptions('kg')
        ]);

        $constraint = new UniqueProductUnitShippingOptions();
        $this->validator->validate($value, $constraint);
        $this->assertNoViolation();
    }

    public function testValidateWithDuplications()
    {
        $value = new ArrayCollection([
            $this->createProductShippingOptions('kg'),
            $this->createProductShippingOptions('kg')
        ]);

        $constraint = new UniqueProductUnitShippingOptions();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testUnexpectedValue()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "array or Traversable and ArrayAccess", "string" given'
        );

        $constraint = new UniqueProductUnitShippingOptions();
        $this->validator->validate('test', $constraint);
    }

    public function testUnexpectedItem()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Bundle\ProductBundle\Model\ProductUnitHolderInterface", "stdClass" given'
        );
        $value = new ArrayCollection([ new \stdClass()]);

        $constraint = new UniqueProductUnitShippingOptions();
        $this->validator->validate($value, $constraint);
    }

    private function createProductShippingOptions(string $code): ProductShippingOptions
    {
        $unit = $this->createMock(ProductUnit::class);
        $unit->expects($this->atLeastOnce())
            ->method('getCode')
            ->willReturn($code);

        $option = $this->createMock(ProductShippingOptions::class);
        $option->expects($this->atLeastOnce())
            ->method('getProductUnit')
            ->willReturn($unit);

        return $option;
    }
}
