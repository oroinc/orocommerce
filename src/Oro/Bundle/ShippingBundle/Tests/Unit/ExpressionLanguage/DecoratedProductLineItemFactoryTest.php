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
use Oro\Bundle\ShippingBundle\ExpressionLanguage\DecoratedProductLineItemFactory;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Bundle\ShippingBundle\Tests\Unit\Context\ShippingLineItemTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DecoratedProductLineItemFactoryTest extends TestCase
{
    use ShippingLineItemTrait;

    private DecoratedProductLineItemFactory $testedDecoratedProductLineItemFactory;

    private VirtualFieldsProductDecoratorFactory|MockObject $virtualFieldsProductDecoratorFactory;

    #[\Override]
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

        $productUnit = $this->createMock(ProductUnit::class);
        $unitCode = 'unit_code';
        $quantity = 1;
        $productHolder = $this->createMock(ProductHolderInterface::class);
        $price = Price::create(1, 'USD');
        $kitItem = new ProductKitItem();
        $sortOrder = 1;

        $shippingKitItemLineItemToDecorate = (new ShippingKitItemLineItem($productHolder))
            ->setQuantity($quantity)
            ->setProductUnit($productUnit)
            ->setProductUnitCode($unitCode)
            ->setProduct($product2)
            ->setProductSku($product2->getSku())
            ->setPrice($price)
            ->setKitItem($kitItem)
            ->setSortOrder($sortOrder);

        $shippingKitItemLineItemsToDecorate = new ArrayCollection([
            $shippingKitItemLineItemToDecorate,
        ]);

        $shippingLineItemToDecorate = $this->getShippingLineItem(quantity: 20, unitCode: 'each')
            ->setPrice($this->createMock(Price::class))
            ->setProduct($product1)
            ->setProductSku('sku')
            ->setDimensions($this->createMock(Dimensions::class))
            ->setWeight($this->createMock(Weight::class))
            ->setKitItemLineItems($shippingKitItemLineItemsToDecorate)
            ->setChecksum('checksum_1');

        $shippingKitItemLineItemWithDecoratedProduct = (new ShippingKitItemLineItem($productHolder))
            ->setQuantity($quantity)
            ->setProductUnit($productUnit)
            ->setProductUnitCode($unitCode)
            ->setProduct($decoratedProduct2Mock)
            ->setProductSku($product2->getSku())
            ->setPrice($price)
            ->setKitItem($kitItem)
            ->setSortOrder($sortOrder);

        $kitItemLineItemsWithDecoratedProduct = new ArrayCollection([
            $shippingKitItemLineItemWithDecoratedProduct,
        ]);

        $expectedShippingLineItem = $this->getShippingLineItem(quantity: 20, unitCode: 'each')
            ->setPrice($this->createMock(Price::class))
            ->setProduct($decoratedProduct1Mock)
            ->setProductSku('sku')
            ->setDimensions($this->createMock(Dimensions::class))
            ->setWeight($this->createMock(Weight::class))
            ->setKitItemLineItems($kitItemLineItemsWithDecoratedProduct)
            ->setChecksum('checksum_1');

        $actualLineItem = $this->testedDecoratedProductLineItemFactory->createShippingLineItemWithDecoratedProduct(
            $shippingLineItemToDecorate,
            $products
        );

        self::assertEquals($expectedShippingLineItem, $actualLineItem);
    }
}
