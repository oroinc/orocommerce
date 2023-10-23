<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\ExpressionLanguage;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecorator;
use Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingKitItemLineItem;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\ExpressionLanguage\DecoratedProductLineItemFactory;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DecoratedProductLineItemFactoryTest extends TestCase
{
    private DecoratedProductLineItemFactory $testedDecoratedProductLineItemFactory;

    private VirtualFieldsProductDecoratorFactory|MockObject $virtualFieldsProductDecoratorFactory;

    protected function setUp(): void
    {
        $this->virtualFieldsProductDecoratorFactory = $this->createMock(VirtualFieldsProductDecoratorFactory::class);

        $this->testedDecoratedProductLineItemFactory = new DecoratedProductLineItemFactory(
            $this->virtualFieldsProductDecoratorFactory
        );
    }

    public function testCreateShippingLineItemWithDecoratedProduct(): void
    {
        $product1 = new ProductStub();
        $product1->setId(1001);
        $product1->setSku('sku1');
        $product2 = new ProductStub();
        $product2->setId(2002);
        $product2->setSku('sku2');
        $product3 = new ProductStub();
        $product3->setId(3003);

        $products = [$product1, $product2, $product3];

        $decoratedProduct1Mock = $this->createMock(VirtualFieldsProductDecorator::class);
        $decoratedProduct2Mock = $this->createMock(VirtualFieldsProductDecorator::class);

        $this->virtualFieldsProductDecoratorFactory
            ->expects(self::exactly(2))
            ->method('createDecoratedProduct')
            ->willReturnMap([
                [$products, $product1, $decoratedProduct1Mock],
                [$products, $product2, $decoratedProduct2Mock],
            ]);

        $unitCode = 'unit_code';
        $productUnit = (new ProductUnit())->setCode($unitCode);
        $quantity = 1;
        $productHolder = $this->createMock(ProductHolderInterface::class);
        $price = Price::create(1, 'USD');
        $kitItem = new ProductKitItem();
        $sortOrder = 1;

        $shippingKitItemLineItem1ToDecorate = (new ShippingKitItemLineItem($productHolder))
            ->setQuantity($quantity)
            ->setProductUnit($productUnit)
            ->setProductUnitCode($unitCode)
            ->setProduct($product2)
            ->setProductSku($product2->getSku())
            ->setPrice($price)
            ->setKitItem($kitItem)
            ->setSortOrder($sortOrder);

        $shippingLineItemParams = [
            ShippingLineItem::FIELD_PRODUCT => $product1,
            ShippingLineItem::FIELD_PRICE => $this->createMock(Price::class),
            ShippingLineItem::FIELD_PRODUCT_UNIT => $this->createMock(ProductUnit::class),
            ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => 'each',
            ShippingLineItem::FIELD_QUANTITY => 20,
            ShippingLineItem::FIELD_PRODUCT_HOLDER => $this->createMock(ProductHolderInterface::class),
            ShippingLineItem::FIELD_PRODUCT_SKU => 'sku',
            ShippingLineItem::FIELD_WEIGHT => $this->createMock(Weight::class),
            ShippingLineItem::FIELD_DIMENSIONS => $this->createMock(Dimensions::class),
            ShippingLineItem::FIELD_KIT_ITEM_LINE_ITEMS => new ArrayCollection([
                $shippingKitItemLineItem1ToDecorate,
            ]),
            ShippingLineItem::FIELD_CHECKSUM => 'checksum_1',
        ];

        $shippingLineItemToDecorate = new ShippingLineItem($shippingLineItemParams);

        $shippingKitItemLineItem1WithDecoratedProduct = (new ShippingKitItemLineItem($productHolder))
            ->setQuantity($quantity)
            ->setProductUnit($productUnit)
            ->setProductUnitCode($unitCode)
            ->setProduct($decoratedProduct2Mock)
            ->setProductSku($product2->getSku())
            ->setPrice($price)
            ->setKitItem($kitItem)
            ->setSortOrder($sortOrder);

        $shippingLineItemParams[ShippingLineItem::FIELD_PRODUCT] = $decoratedProduct1Mock;
        $shippingLineItemParams[ShippingLineItem::FIELD_KIT_ITEM_LINE_ITEMS] = new ArrayCollection([
            $shippingKitItemLineItem1WithDecoratedProduct,
        ]);

        $expectedShippingLineItem = new ShippingLineItem($shippingLineItemParams);

        $actualLineItem = $this->testedDecoratedProductLineItemFactory->createShippingLineItemWithDecoratedProduct(
            $shippingLineItemToDecorate,
            $products
        );

        self::assertEquals($expectedShippingLineItem, $actualLineItem);
    }
}
