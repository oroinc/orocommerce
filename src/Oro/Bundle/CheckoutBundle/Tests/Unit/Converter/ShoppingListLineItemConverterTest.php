<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Converter\ProductKitItemLineItemConverter;
use Oro\Bundle\CheckoutBundle\Converter\ShoppingListLineItemConverter;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutProductKitItemLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use PHPUnit\Framework\TestCase;

class ShoppingListLineItemConverterTest extends TestCase
{
    private ShoppingListLineItemConverter $converter;

    #[\Override]
    protected function setUp(): void
    {
        $this->converter = new ShoppingListLineItemConverter(new ProductKitItemLineItemConverter());
    }

    /**
     * @dataProvider isSourceSupportedDataProvider
     */
    public function testIsSourceSupported(bool $expected, mixed $source): void
    {
        self::assertEquals($expected, $this->converter->isSourceSupported($source));
    }

    public function isSourceSupportedDataProvider(): array
    {
        return [
            'positive' => ['expected' => true, 'source' => $this->createMock(ShoppingList::class)],
            'unsupported instance' => ['expected' => false, 'source' => new \stdClass()],
        ];
    }

    /**
     * @dataProvider convertDataProvider
     *
     * @param ShoppingList $shoppingList
     * @param Collection<CheckoutLineItem> $expected
     */
    public function testConvert(ShoppingList $shoppingList, Collection $expected): void
    {
        /** @var CheckoutLineItem[] $checkoutLineItems */
        $checkoutLineItems = $this->converter->convert($shoppingList);
        self::assertInstanceOf(ArrayCollection::class, $checkoutLineItems);
        self::assertCount(1, $checkoutLineItems);

        self::assertEquals($expected, $checkoutLineItems);
    }

    public function convertDataProvider(): iterable
    {
        $product = (new Product())->setSku('SKU1');
        $parentProduct = new Product();
        $productUnitItem = (new ProductUnit())->setCode('item');

        $regularLineItem = (new LineItem())
            ->setProduct($product)
            ->setParentProduct($parentProduct)
            ->setUnit($productUnitItem)
            ->setNotes('sample notes')
            ->setQuantity(12.3456);

        $regularCheckoutLineItem = (new CheckoutLineItem())
            ->setFromExternalSource(false)
            ->setPriceFixed(false)
            ->setProduct($regularLineItem->getProduct())
            ->setParentProduct($regularLineItem->getParentProduct())
            ->setProductSku($regularLineItem->getProductSku())
            ->setProductUnit($regularLineItem->getProductUnit())
            ->setProductUnitCode($regularLineItem->getProductUnitCode())
            ->setQuantity($regularLineItem->getQuantity())
            ->setComment($regularLineItem->getNotes())
            ->setChecksum($regularLineItem->getChecksum());

        yield 'regular line item' => [
            (new ShoppingList())->addLineItem($regularLineItem),
            new ArrayCollection([$regularCheckoutLineItem]),
        ];

        $productKit = (new Product())
            ->setType(Product::TYPE_KIT);

        $kitItemProduct1 = new Product();
        $kitItem1 = new ProductKitItem();
        $productUnitEach = (new ProductUnit())->setCode('each');
        $kitItemLineItem1 = (new ProductKitItemLineItem())
            ->setKitItem($kitItem1)
            ->setProduct($kitItemProduct1)
            ->setQuantity(1.2345)
            ->setUnit($productUnitEach)
            ->setSortOrder(11);

        $kitItemProduct2 = new Product();
        $kitItem2 = new ProductKitItem();
        $productUnitKg = (new ProductUnit())->setCode('kg');
        $kitItemLineItem2 = (new ProductKitItemLineItem())
            ->setKitItem($kitItem2)
            ->setProduct($kitItemProduct2)
            ->setQuantity(2.2345)
            ->setUnit($productUnitKg)
            ->setSortOrder(22);

        $lineItemWithKitItemLineItems = (new LineItem())
            ->setProduct($productKit)
            ->setUnit($productUnitItem)
            ->setNotes('sample notes')
            ->setQuantity(12.3456)
            ->setChecksum('line_item1')
            ->addKitItemLineItem($kitItemLineItem1)
            ->addKitItemLineItem($kitItemLineItem2);

        $checkoutKitItemLineItem1 = (new CheckoutProductKitItemLineItem())
            ->setProduct($kitItemLineItem1->getProduct())
            ->setKitItem($kitItemLineItem1->getKitItem())
            ->setProductUnit($kitItemLineItem1->getProductUnit())
            ->setQuantity($kitItemLineItem1->getQuantity())
            ->setSortOrder($kitItemLineItem1->getSortOrder())
            ->setPriceFixed(false);

        $checkoutKitItemLineItem2 = (new CheckoutProductKitItemLineItem())
            ->setProduct($kitItemLineItem2->getProduct())
            ->setKitItem($kitItemLineItem2->getKitItem())
            ->setProductUnit($kitItemLineItem2->getProductUnit())
            ->setQuantity($kitItemLineItem2->getQuantity())
            ->setSortOrder($kitItemLineItem2->getSortOrder())
            ->setPriceFixed(false);

        $checkoutLineItemWithKitItemLineItems = (new CheckoutLineItem())
            ->setFromExternalSource(false)
            ->setPriceFixed(false)
            ->setProduct($lineItemWithKitItemLineItems->getProduct())
            ->setProductSku($lineItemWithKitItemLineItems->getProductSku())
            ->setProductUnit($lineItemWithKitItemLineItems->getProductUnit())
            ->setProductUnitCode($lineItemWithKitItemLineItems->getProductUnitCode())
            ->setQuantity($lineItemWithKitItemLineItems->getQuantity())
            ->setComment($lineItemWithKitItemLineItems->getNotes())
            ->setChecksum($lineItemWithKitItemLineItems->getChecksum())
            ->addKitItemLineItem($checkoutKitItemLineItem1)
            ->addKitItemLineItem($checkoutKitItemLineItem2);

        yield 'line item with kit item line items' => [
            (new ShoppingList())->addLineItem($lineItemWithKitItemLineItems),
            new ArrayCollection([$checkoutLineItemWithKitItemLineItems]),
        ];
    }
}
