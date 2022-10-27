<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\ExpressionLanguage;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecorator;
use Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\ExpressionLanguage\DecoratedProductLineItemFactory;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;

class DecoratedProductLineItemFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DecoratedProductLineItemFactory
     */
    private $testedDecoratedProductLineItemFactory;

    /**
     * @var VirtualFieldsProductDecoratorFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $virtualFieldsProductDecoratorFactory;

    protected function setUp(): void
    {
        $this->virtualFieldsProductDecoratorFactory = $this->createMock(VirtualFieldsProductDecoratorFactory::class);

        $this->testedDecoratedProductLineItemFactory = new DecoratedProductLineItemFactory(
            $this->virtualFieldsProductDecoratorFactory
        );
    }

    public function testCreateShippingLineItemWithDecoratedProduct(): void
    {
        $shippingLineItemParams = [
            ShippingLineItem::FIELD_PRICE => $this->createMock(Price::class),
            ShippingLineItem::FIELD_PRODUCT_UNIT => $this->createMock(ProductUnit::class),
            ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => 'each',
            ShippingLineItem::FIELD_QUANTITY => 20,
            ShippingLineItem::FIELD_PRODUCT_HOLDER => $this->createMock(ProductHolderInterface::class),
            ShippingLineItem::FIELD_PRODUCT_SKU => 'sku',
            ShippingLineItem::FIELD_WEIGHT => $this->createMock(Weight::class),
            ShippingLineItem::FIELD_DIMENSIONS => $this->createMock(Dimensions::class)
        ];

        $product1 = new ProductStub();
        $product1->setId(1001);
        $product2 = new ProductStub();
        $product2->setId(2002);
        $product3 = new ProductStub();
        $product3->setId(3003);

        $products = [$product1, $product2, $product3];

        $decoratedProductMock = $this->createMock(VirtualFieldsProductDecorator::class);
        $productToDecorate = $this->createMock(Product::class);

        $this->virtualFieldsProductDecoratorFactory
            ->expects($this->once())
            ->method('createDecoratedProduct')
            ->with($products, $productToDecorate)
            ->willReturn($decoratedProductMock);

        $shippingLineItemParams[ShippingLineItem::FIELD_PRODUCT] = $productToDecorate;
        $shippingLineItemToDecorate = new ShippingLineItem($shippingLineItemParams);

        $shippingLineItemParams[ShippingLineItem::FIELD_PRODUCT] = $decoratedProductMock;
        $expectedShippingLineItem = new ShippingLineItem($shippingLineItemParams);

        $actualLineItem = $this->testedDecoratedProductLineItemFactory->createShippingLineItemWithDecoratedProduct(
            $shippingLineItemToDecorate,
            $products
        );

        static::assertEquals($expectedShippingLineItem, $actualLineItem);
    }
}
