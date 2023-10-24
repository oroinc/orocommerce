<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductKitLineItemContainsRequiredKitItems;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductKitLineItemContainsRequiredKitItemsValidator;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ProductKitLineItemContainsRequiredKitItemsValidatorTest extends ConstraintValidatorTestCase
{
    private LocalizationHelper|MockObject $localizationHelper;

    private Localization $localization;

    protected function setUp(): void
    {
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);

        $this->localization = $this->createMock(Localization::class);
        $this->localizationHelper
            ->method('getCurrentLocalization')
            ->willReturn($this->localization);

        $this->localizationHelper
            ->method('getLocalizedValue')
            ->with(self::isInstanceOf(Collection::class), $this->localization)
            ->willReturnCallback(static fn (Collection $value) => $value[0]);

        parent::setUp();
    }

    protected function createValidator(): ProductKitLineItemContainsRequiredKitItemsValidator
    {
        return new ProductKitLineItemContainsRequiredKitItemsValidator($this->localizationHelper);
    }

    public function testValidateWhenNull(): void
    {
        $this->validator->validate(null, new ProductKitLineItemContainsRequiredKitItems());

        $this->assertNoViolation();
    }

    public function testValidateWhenNoProduct(): void
    {
        $this->validator->validate(null, new ProductKitLineItemContainsRequiredKitItems());

        $this->assertNoViolation();
    }

    public function testValidateWhenNoKitItemLineItemsAndNoProduct(): void
    {
        $kitItemLineItem1 = new ProductKitItemLineItem();
        $lineItem = (new LineItem())
            ->addKitItemLineItem($kitItemLineItem1);

        $this->validator->validate($lineItem, new ProductKitLineItemContainsRequiredKitItems());

        $this->assertNoViolation();
    }

    public function testValidateWhenNoKitItemLineItemsAndNoKitItems(): void
    {
        $kitItemLineItem1 = new ProductKitItemLineItem();
        $lineItem = (new LineItem())
            ->setProduct(new Product())
            ->addKitItemLineItem($kitItemLineItem1);

        $this->validator->validate($lineItem, new ProductKitLineItemContainsRequiredKitItems());

        $this->assertNoViolation();
    }

    public function testValidateWhenNoRequiredKitItems(): void
    {
        $kitItem1 = (new ProductKitItem())->setOptional(true);
        $kitItem2 = (new ProductKitItem())->setOptional(true);

        $kitItemLineItem1 = (new ProductKitItemLineItem())
            ->setKitItem($kitItem1);
        $kitItemLineItem2 = (new ProductKitItemLineItem())
            ->setKitItem($kitItem2);

        $productKit = (new Product())
            ->addKitItem($kitItem1)
            ->addKitItem($kitItem2);

        $lineItem = (new LineItem())
            ->setProduct($productKit)
            ->addKitItemLineItem($kitItemLineItem1)
            ->addKitItemLineItem($kitItemLineItem2);

        $this->validator->validate($lineItem, new ProductKitLineItemContainsRequiredKitItems());

        $this->assertNoViolation();
    }

    public function testValidateWhenHasRequiredKitItemLineItems(): void
    {
        $kitItem1 = (new ProductKitItem())->setOptional(true);
        $kitItem2 = (new ProductKitItem())->setOptional(false);
        $kitItem3 = (new ProductKitItem())->setOptional(true);

        $kitItemLineItem1 = (new ProductKitItemLineItem())
            ->setKitItem($kitItem1);
        $kitItemLineItem2 = (new ProductKitItemLineItem())
            ->setKitItem($kitItem2);

        $productKit = (new Product())
            ->addKitItem($kitItem1)
            ->addKitItem($kitItem2)
            ->addKitItem($kitItem3);

        $lineItem = (new LineItem())
            ->setProduct($productKit)
            ->addKitItemLineItem($kitItemLineItem1)
            ->addKitItemLineItem($kitItemLineItem2);

        $this->validator->validate($lineItem, new ProductKitLineItemContainsRequiredKitItems());

        $this->assertNoViolation();
    }

    public function testValidateWhenNoRequiredKitItemLineItems(): void
    {
        $kitItem1 = (new ProductKitItem())->setOptional(false);
        $kitItem2 = (new ProductKitItem())->setOptional(true);
        $kitItem3 = (new ProductKitItemStub())
            ->setOptional(false)
            ->setDefaultLabel('sample kit item');

        $kitItemLineItem1 = (new ProductKitItemLineItem())
            ->setKitItem($kitItem1);

        $productKit = (new Product())
            ->setSku('sample-sku')
            ->addKitItem($kitItem1)
            ->addKitItem($kitItem2)
            ->addKitItem($kitItem3);

        $lineItem = (new LineItem())
            ->setProduct($productKit)
            ->addKitItemLineItem($kitItemLineItem1);

        $constraint = new ProductKitLineItemContainsRequiredKitItems();
        $this->validator->validate($lineItem, $constraint);

        $this
            ->buildViolation($constraint->message)
            ->setParameters([
                '{{ product_kit_sku }}' => '"' . $lineItem->getProductSku() . '"',
                '{{ product_kit_item_label }}' => '"' . (string)$kitItem3->getDefaultLabel() . '"',
            ])
            ->atPath('property.path.kitItemLineItems')
            ->setCause($lineItem)
            ->setCode(ProductKitLineItemContainsRequiredKitItems::MISSING_REQUIRED_KIT_ITEM)
            ->assertRaised();
    }
}
