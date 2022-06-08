<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Service\ProductKitItemProductUnitChecker;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductKitItemUnitAvailableForSpecifiedProducts;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductKitItemUnitAvailableForSpecifiedProductsValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\IsNull;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ProductKitItemUnitAvailableForSpecifiedProductsValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ProductKitItemUnitAvailableForSpecifiedProductsValidator
    {
        return new ProductKitItemUnitAvailableForSpecifiedProductsValidator(new ProductKitItemProductUnitChecker());
    }

    public function testGetTargets(): void
    {
        $constraint = new ProductKitItemUnitAvailableForSpecifiedProducts();

        self::assertEquals([Constraint::CLASS_CONSTRAINT], $constraint->getTargets());
    }

    public function testValidateUnsupportedConstraint(): void
    {
        $constraint = new IsNull();

        $this->expectExceptionObject(
            new UnexpectedTypeException($constraint, ProductKitItemUnitAvailableForSpecifiedProducts::class)
        );

        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testValidateUnsupportedClass(): void
    {
        $value = new \stdClass();

        $this->expectExceptionObject(new UnexpectedValueException($value, ProductKitItem::class));

        $constraint = new ProductKitItemUnitAvailableForSpecifiedProducts();
        $this->validator->validate($value, $constraint);
    }

    public function testValidateUnitNotAvailableForAllSpecifiedProducts(): void
    {
        $productUnitItem = (new ProductUnit())->setCode('item');
        $productUnitSet = (new ProductUnit())->setCode('set');

        $productFooUnitPrecision = (new ProductUnitPrecision())->setUnit($productUnitItem);
        $productFoo = (new ProductStub())
            ->setId(1)
            ->addUnitPrecision($productFooUnitPrecision);

        $productBarUnitPrecision = (new ProductUnitPrecision())->setUnit($productUnitSet);
        $productBar = (new ProductStub())
            ->setId(2)
            ->addUnitPrecision($productBarUnitPrecision);

        $productBaz = new ProductStub();

        $kitItem = (new ProductKitItemStub(42))
            ->setProductUnit($productUnitItem)
            ->addProduct($productFoo)
            ->addProduct($productBar)
            ->addProduct($productBaz);

        $constraint = new ProductKitItemUnitAvailableForSpecifiedProducts();
        $this->validator->validate($kitItem, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.products.1')
            ->setCode(ProductKitItemUnitAvailableForSpecifiedProducts::PRODUCT_UNIT_NOT_ALLOWED)
            ->buildNextViolation($constraint->message)
            ->atPath('property.path.products.2')
            ->setCode(ProductKitItemUnitAvailableForSpecifiedProducts::PRODUCT_UNIT_NOT_ALLOWED)
            ->assertRaised();
    }

    public function testValidateUnitAvailableForAllSpecifiedProducts(): void
    {
        $productUnitItem = (new ProductUnit())->setCode('item');

        $productFooUnitPrecision = (new ProductUnitPrecision())->setUnit($productUnitItem);
        $productFoo = (new ProductStub())
            ->setId(1)
            ->addUnitPrecision($productFooUnitPrecision);

        $productBarUnitPrecision = (new ProductUnitPrecision())->setUnit($productUnitItem);
        $productBar = (new ProductStub())
            ->setId(2)
            ->addUnitPrecision($productBarUnitPrecision);

        $kitItem = (new ProductKitItemStub(42))
            ->setProductUnit($productUnitItem)
            ->addProduct($productFoo)
            ->addProduct($productBar);

        $constraint = new ProductKitItemUnitAvailableForSpecifiedProducts();
        $this->validator->validate($kitItem, $constraint);

        $this->assertNoViolation();
    }
}
