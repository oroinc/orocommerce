<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Validator\Constraints\NotEmptyProductKitItemsCollection;
use Oro\Bundle\ProductBundle\Validator\Constraints\NotEmptyProductKitItemsCollectionValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\IsNull;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class NotEmptyProductKitItemsCollectionValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): NotEmptyProductKitItemsCollectionValidator
    {
        return new NotEmptyProductKitItemsCollectionValidator();
    }

    public function testGetTargets(): void
    {
        $constraint = new NotEmptyProductKitItemsCollection();

        self::assertEquals([Constraint::CLASS_CONSTRAINT], $constraint->getTargets());
    }

    public function testValidateUnsupportedConstraint(): void
    {
        $constraint = new IsNull();

        $this->expectExceptionObject(
            new UnexpectedTypeException($constraint, NotEmptyProductKitItemsCollection::class)
        );

        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testValidateUnsupportedClass(): void
    {
        $value = new \stdClass();

        $this->expectExceptionObject(new UnexpectedValueException($value, Product::class));

        $constraint = new NotEmptyProductKitItemsCollection();
        $this->validator->validate($value, $constraint);
    }

    public function testValidateNotProductKitType(): void
    {
        $product = new Product();
        $product->setType(Product::TYPE_SIMPLE);

        $constraint = new NotEmptyProductKitItemsCollection();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateKitItemsCollectionEmpty(): void
    {
        $product = new Product();
        $product->setType(Product::TYPE_KIT);

        $constraint = new NotEmptyProductKitItemsCollection();
        $this->validator->validate($product, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.kitItems')
            ->assertRaised();
    }

    public function testValidateKitItemsCollectionNotEmpty(): void
    {
        $kitItem = new ProductKitItemStub(42);

        $product = new Product();
        $product->setType(Product::TYPE_KIT);
        $product->addKitItem($kitItem);

        $constraint = new NotEmptyProductKitItemsCollection();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }
}
