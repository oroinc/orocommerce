<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\ExpressionLanguage;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PaymentBundle\Context\PaymentKitItemLineItem;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\PaymentBundle\ExpressionLanguage\DecoratedProductLineItemFactory;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecorator;
use Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory;
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

    public function testCreatePaymentLineItemWithDecoratedProduct(): void
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

        $paymentKitItemLineItem1ToDecorate = (new PaymentKitItemLineItem(
            $productUnit,
            $quantity,
            $productHolder
        ))
            ->setProduct($product2)
            ->setProductSku($product2->getSku())
            ->setPrice($price)
            ->setKitItem($kitItem)
            ->setSortOrder($sortOrder);

        $paymentLineItemParams = [
            PaymentLineItem::FIELD_PRODUCT => $product1,
            PaymentLineItem::FIELD_PRICE => $this->createMock(Price::class),
            PaymentLineItem::FIELD_PRODUCT_UNIT => $this->createMock(ProductUnit::class),
            PaymentLineItem::FIELD_PRODUCT_UNIT_CODE => 'each',
            PaymentLineItem::FIELD_QUANTITY => 20,
            PaymentLineItem::FIELD_PRODUCT_HOLDER => $this->createMock(ProductHolderInterface::class),
            PaymentLineItem::FIELD_PRODUCT_SKU => 'sku',
            PaymentLineItem::FIELD_KIT_ITEM_LINE_ITEMS => new ArrayCollection([
                $paymentKitItemLineItem1ToDecorate,
            ]),
            PaymentLineItem::FIELD_CHECKSUM => 'checksum_1',
        ];

        $paymentLineItemToDecorate = new PaymentLineItem($paymentLineItemParams);

        $paymentKitItemLineItem1WithDecoratedProduct = (new PaymentKitItemLineItem(
            $productUnit,
            $quantity,
            $productHolder
        ))
            ->setProduct($decoratedProduct2Mock)
            ->setProductSku($product2->getSku())
            ->setPrice($price)
            ->setKitItem($kitItem)
            ->setSortOrder($sortOrder);

        $paymentLineItemParams[PaymentLineItem::FIELD_PRODUCT] = $decoratedProduct1Mock;
        $paymentLineItemParams[PaymentLineItem::FIELD_KIT_ITEM_LINE_ITEMS] = new ArrayCollection([
            $paymentKitItemLineItem1WithDecoratedProduct,
        ]);

        $expectedPaymentLineItem = new PaymentLineItem($paymentLineItemParams);

        $actualLineItem = $this->testedDecoratedProductLineItemFactory->createPaymentLineItemWithDecoratedProduct(
            $paymentLineItemToDecorate,
            $products
        );

        self::assertEquals($expectedPaymentLineItem, $actualLineItem);
    }
}
