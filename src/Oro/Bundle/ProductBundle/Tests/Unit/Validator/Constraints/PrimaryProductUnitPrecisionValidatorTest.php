<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Validator\Constraints\PrimaryProductUnitPrecision;
use Oro\Bundle\ProductBundle\Validator\Constraints\PrimaryProductUnitPrecisionValidator;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class PrimaryProductUnitPrecisionValidatorTest extends ConstraintValidatorTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function createValidator()
    {
        return new PrimaryProductUnitPrecisionValidator();
    }

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        $this->constraint = new PrimaryProductUnitPrecision();
        $this->propertyPath = null;

        return parent::createContext();
    }

    public function testValidateWithWrongConstraint()
    {
        $this->expectException(\Symfony\Component\Validator\Exception\UnexpectedTypeException::class);
        $this->expectExceptionMessage(\sprintf(
            'Expected argument of type "%s", "%s" given',
            \Oro\Bundle\ProductBundle\Validator\Constraints\PrimaryProductUnitPrecision::class,
            \Symfony\Component\Validator\Constraints\NotNull::class
        ));

        $constraint = new NotNull();
        $this->validator->validate(new Product(), $constraint);
    }

    public function testValidateWithNotTheProductUnitPrecisionEntity()
    {
        $this->expectException(\Symfony\Component\Validator\Exception\UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Bundle\ProductBundle\Entity\Product", "stdClass" given'
        );

        $this->validator->validate(new \stdClass(), $this->constraint);
    }

    public function testValidateWithEmptyPrimaryProductUnitPrecision()
    {
        $value = new Product();
        $this->validator->validate($value, $this->constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithAbsentPrimaryPrecision()
    {
        $value = new Product();
        $precision = new ProductUnitPrecision();
        $this->setEntityId($precision, 23);
        $value->addUnitPrecision($precision);

        $this->validator->validate($value, $this->constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithCorrectData()
    {
        $value = new Product();
        $precision = new ProductUnitPrecision();
        $precision->setUnit(new ProductUnit());
        $this->setEntityId($precision, 23);
        $value->setPrimaryUnitPrecision($precision);

        $this->validator->validate($value, $this->constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithAbsentPrimaryUnitPrecisionInCollection()
    {
        $value = new Product();
        $precision = new ProductUnitPrecision();
        $precision->setUnit(new ProductUnit());
        $this->setEntityId($precision, 23);
        $value->setPrimaryUnitPrecision($precision);

        $value->getUnitPrecisions()->clear();

        $this->validator->validate($value, $this->constraint);

        $this->buildViolation($this->constraint->message)
            ->atPath('unitPrecisions')
            ->assertRaised();
    }

    private function setEntityId($entity, $id)
    {
        $reflectionClass = new \ReflectionClass($entity);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($entity, $id);
    }
}
