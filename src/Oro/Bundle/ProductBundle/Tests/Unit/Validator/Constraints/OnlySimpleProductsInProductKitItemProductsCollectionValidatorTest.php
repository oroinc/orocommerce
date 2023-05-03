<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ProductBundle\Validator\Constraints\OnlySimpleProductsInProductKitItemProductsCollection;
use Oro\Bundle\ProductBundle\Validator\Constraints\OnlySimpleProductsInProductKitItemProductsCollectionValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\IsNull;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class OnlySimpleProductsInProductKitItemProductsCollectionValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): OnlySimpleProductsInProductKitItemProductsCollectionValidator
    {
        return new OnlySimpleProductsInProductKitItemProductsCollectionValidator();
    }

    public function testGetTargets(): void
    {
        $constraint = new OnlySimpleProductsInProductKitItemProductsCollection();

        self::assertEquals([Constraint::CLASS_CONSTRAINT], $constraint->getTargets());
    }

    public function testValidateUnsupportedConstraint(): void
    {
        $constraint = new IsNull();

        $this->expectExceptionObject(
            new UnexpectedTypeException($constraint, OnlySimpleProductsInProductKitItemProductsCollection::class)
        );

        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testValidateUnsupportedClass(): void
    {
        $value = new \stdClass();

        $this->expectExceptionObject(new UnexpectedValueException($value, ProductKitItem::class));

        $constraint = new OnlySimpleProductsInProductKitItemProductsCollection();
        $this->validator->validate($value, $constraint);
    }

    /**
     * @dataProvider validateNotSimpleProductsDataProvider
     */
    public function testValidateNotSimpleProducts(string $unsupportedProductType): void
    {
        $productFoo = new ProductStub();
        $productFoo->setId(1);
        $productFoo->setType($unsupportedProductType);

        $productBar = new ProductStub();
        $productBar->setId(2);
        $productBar->setType($unsupportedProductType);

        $productBaz = new ProductStub();
        $productBaz->setType($unsupportedProductType);

        $kitItem = new ProductKitItemStub(42);
        $kitItem->addKitItemProduct((new ProductKitItemProduct())->setProduct($productFoo));
        $kitItem->addKitItemProduct((new ProductKitItemProduct())->setProduct($productBar));
        $kitItem->addKitItemProduct((new ProductKitItemProduct())->setProduct($productBaz));

        $constraint = new OnlySimpleProductsInProductKitItemProductsCollection();
        $this->validator->validate($kitItem, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.kitItemProducts.0')
            ->buildNextViolation($constraint->message)
            ->atPath('property.path.kitItemProducts.1')
            ->buildNextViolation($constraint->message)
            ->atPath('property.path.kitItemProducts.2')
            ->assertRaised();
    }

    public function validateNotSimpleProductsDataProvider(): array
    {
        return [
            [Product::TYPE_CONFIGURABLE],
            [Product::TYPE_KIT],
            ['unknownType'],
            [''],
        ];
    }

    public function testValidateOnlySimpleProducts(): void
    {
        $product = new Product();
        $product->setType(Product::TYPE_SIMPLE);

        $kitItem = new ProductKitItemStub(42);
        $kitItem->addKitItemProduct((new ProductKitItemProduct())->setProduct($product));

        $constraint = new OnlySimpleProductsInProductKitItemProductsCollection();
        $this->validator->validate($kitItem, $constraint);

        $this->assertNoViolation();
    }
}
