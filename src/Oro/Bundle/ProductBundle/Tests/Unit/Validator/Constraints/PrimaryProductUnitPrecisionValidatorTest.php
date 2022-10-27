<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Validator\Constraints\PrimaryProductUnitPrecision;
use Oro\Bundle\ProductBundle\Validator\Constraints\PrimaryProductUnitPrecisionValidator;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class PrimaryProductUnitPrecisionValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): PrimaryProductUnitPrecisionValidator
    {
        return new PrimaryProductUnitPrecisionValidator();
    }

    public function testValidateWithWrongConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(\sprintf(
            'Expected argument of type "%s", "%s" given',
            PrimaryProductUnitPrecision::class,
            NotNull::class
        ));

        $constraint = new NotNull();
        $this->validator->validate(new Product(), $constraint);
    }

    public function testValidateWithNotTheProductUnitPrecisionEntity(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Bundle\ProductBundle\Entity\Product", "stdClass" given'
        );

        $constraint = new PrimaryProductUnitPrecision();
        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testValidateWithEmptyPrimaryProductUnitPrecision(): void
    {
        $value = new Product();

        $constraint = new PrimaryProductUnitPrecision();
        $this->validator->validate($value, $constraint);
        $this->assertNoViolation();
    }

    public function testValidateWithAbsentPrimaryPrecision(): void
    {
        $value = new Product();
        $precision = new ProductUnitPrecision();
        ReflectionUtil::setId($precision, 23);
        $value->addUnitPrecision($precision);

        $constraint = new PrimaryProductUnitPrecision();
        $this->validator->validate($value, $constraint);
        $this->assertNoViolation();
    }

    public function testValidateWithCorrectData(): void
    {
        $value = new Product();
        $precision = new ProductUnitPrecision();
        ReflectionUtil::setId($precision, 23);
        $precision->setUnit(new ProductUnit());
        $value->setPrimaryUnitPrecision($precision);

        $constraint = new PrimaryProductUnitPrecision();
        $this->validator->validate($value, $constraint);
        $this->assertNoViolation();
    }

    public function testValidateWithAbsentPrimaryUnitPrecisionInCollection(): void
    {
        $value = new Product();
        $precision = new ProductUnitPrecision();
        ReflectionUtil::setId($precision, 23);
        $precision->setUnit(new ProductUnit());
        $value->setPrimaryUnitPrecision($precision);

        $value->getUnitPrecisions()->clear();

        $constraint = new PrimaryProductUnitPrecision();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.unitPrecisions')
            ->assertRaised();
    }
}
