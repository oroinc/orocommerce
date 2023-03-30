<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductKitItemsOwnedByProductKit;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductKitItemsOwnedByProductKitValidator;
use Symfony\Component\Validator\Constraints\IsNull;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ProductKitItemsOwnedByProductKitValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ProductKitItemsOwnedByProductKitValidator
    {
        $localizationHelper = $this->createMock(LocalizationHelper::class);
        $localizationHelper
            ->expects(self::any())
            ->method('getLocalizedValue')
            ->willReturnCallback(static fn (Collection $values) => (string)$values[0]);

        return new ProductKitItemsOwnedByProductKitValidator($localizationHelper);
    }

    public function testValidateUnsupportedConstraint(): void
    {
        $constraint = new IsNull();

        $this->expectExceptionObject(
            new UnexpectedTypeException($constraint, ProductKitItemsOwnedByProductKit::class)
        );

        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testValidateUnsupportedClass(): void
    {
        $value = new \stdClass();

        $this->expectExceptionObject(new UnexpectedValueException($value, Product::class));

        $constraint = new ProductKitItemsOwnedByProductKit();
        $this->validator->validate($value, $constraint);
    }

    public function testValidateUnsupportedWhenProductNull(): void
    {
        $constraint = new ProductKitItemsOwnedByProductKit();

        $this->validator->validate(null, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateNotProductKitType(): void
    {
        $kitItem = new ProductKitItem();
        $productKitItemLabel = (new ProductKitItemLabel())->setString('Kit Item Label');
        $kitItem->addLabel($productKitItemLabel);

        (new Product())
            ->setType(Product::TYPE_KIT)
            ->setSku('SKU1')
            ->addKitItem($kitItem);

        $productKit2 = (new Product())
            ->setSku('SKU2')
            ->setType(Product::TYPE_SIMPLE)
            ->addKitItem($kitItem);

        $constraint = new ProductKitItemsOwnedByProductKit();
        $this->validator->validate($productKit2, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenKitItemOwnedByAnotherProduct(): void
    {
        $kitItem = new ProductKitItem();
        $productKitItemLabel = (new ProductKitItemLabel())->setString('Kit Item Label');
        $kitItem->addLabel($productKitItemLabel);

        $productKit1 = (new Product())
            ->setType(Product::TYPE_KIT)
            ->setSku('SKU1')
            ->addKitItem($kitItem);

        $productKit2 = (new Product())
            ->setSku('SKU2')
            ->setType(Product::TYPE_KIT)
            ->addKitItem($kitItem);

        $constraint = new ProductKitItemsOwnedByProductKit();
        $this->validator->validate($productKit2, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter(
                '{{ kit_item_label }}',
                '"' . $productKitItemLabel . '"'
            )
            ->setParameter('{{ product_kit_sku }}', '"' . $productKit1->getSku() . '"')
            ->atPath('property.path.kitItems.0')
            ->setCode(ProductKitItemsOwnedByProductKit::KIT_ITEM_IS_NOT_OWNED_ERROR)
            ->assertRaised();
    }

    public function testValidate(): void
    {
        $kitItem = new ProductKitItem();
        $productKitItemLabel = (new ProductKitItemLabel())->setString('Kit Item Label');
        $kitItem->addLabel($productKitItemLabel);

        $productKit1 = (new Product())
            ->setSku('SKU2')
            ->setType(Product::TYPE_KIT)
            ->addKitItem($kitItem);

        $constraint = new ProductKitItemsOwnedByProductKit();
        $this->validator->validate($productKit1, $constraint);

        $this->assertNoViolation();
    }
}
