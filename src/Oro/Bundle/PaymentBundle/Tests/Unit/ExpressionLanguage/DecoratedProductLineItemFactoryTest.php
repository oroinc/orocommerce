<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\ExpressionLanguage;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\PaymentBundle\ExpressionLanguage\DecoratedProductLineItemFactory;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecorator;
use Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory;

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

    public function testCreatePaymentLineItemWithDecoratedProduct(): void
    {
        $paymentLineItemParams = [
            PaymentLineItem::FIELD_PRICE => $this->createMock(Price::class),
            PaymentLineItem::FIELD_PRODUCT_UNIT => $this->createMock(ProductUnit::class),
            PaymentLineItem::FIELD_PRODUCT_UNIT_CODE => 'each',
            PaymentLineItem::FIELD_QUANTITY => 20,
            PaymentLineItem::FIELD_PRODUCT_HOLDER => $this->createMock(ProductHolderInterface::class),
            PaymentLineItem::FIELD_PRODUCT_SKU => 'sku',
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

        $paymentLineItemParams[PaymentLineItem::FIELD_PRODUCT] = $productToDecorate;
        $paymentLineItemToDecorate = new PaymentLineItem($paymentLineItemParams);

        $paymentLineItemParams[PaymentLineItem::FIELD_PRODUCT] = $decoratedProductMock;
        $expectedPaymentLineItem = new PaymentLineItem($paymentLineItemParams);

        $actualLineItem = $this->testedDecoratedProductLineItemFactory->createPaymentLineItemWithDecoratedProduct(
            $paymentLineItemToDecorate,
            $products
        );

        static::assertEquals($expectedPaymentLineItem, $actualLineItem);
    }
}
