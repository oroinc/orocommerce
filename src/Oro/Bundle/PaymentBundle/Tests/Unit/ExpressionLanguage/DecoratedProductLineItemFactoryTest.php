<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\EventListenerExpressionLanguage;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;
use Oro\Bundle\PaymentBundle\ExpressionLanguage\DecoratedProductLineItemFactory;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory;

class DecoratedProductLineItemFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DecoratedProductLineItemFactory
     */
    private $testedDecoratedProductLineItemFactory;

    /**
     * @var VirtualFieldsProductDecoratorFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $virtualFieldsProductDecoratorFactory;

    public function setUp()
    {
        $this->virtualFieldsProductDecoratorFactory = $this->createMock(VirtualFieldsProductDecoratorFactory::class);

        $this->testedDecoratedProductLineItemFactory = new DecoratedProductLineItemFactory(
            $this->virtualFieldsProductDecoratorFactory
        );
    }

    /**
     * @return Product|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createProductMock()
    {
        return $this->createMock(Product::class);
    }

    /**
     * @return PaymentLineItemInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createPaymentLineItemMock()
    {
        return $this->createMock(PaymentLineItemInterface::class);
    }

    public function testCreateLineItemWithDecoratedProduct()
    {
        $paymentLineItemParams = [
            PaymentLineItem::FIELD_PRICE => $this->createMock(Price::class),
            PaymentLineItem::FIELD_PRODUCT_UNIT => $this->createMock(ProductUnit::class),
            PaymentLineItem::FIELD_PRODUCT_UNIT_CODE => 'each',
            PaymentLineItem::FIELD_QUANTITY => 20,
            PaymentLineItem::FIELD_PRODUCT_HOLDER => $this->createMock(ProductHolderInterface::class),
            PaymentLineItem::FIELD_PRODUCT_SKU => 'sku',
        ];
        $paymentLineItemMocks = [
            $this->createPaymentLineItemMock(),
            $this->createPaymentLineItemMock(),
            $this->createPaymentLineItemMock(),
        ];
        $decoratedProductMock = $this->createProductMock();
        $productToDecorate = $this->createProductMock();

        $this->virtualFieldsProductDecoratorFactory
            ->expects($this->once())
            ->method('createDecoratedProductByProductHolders')
            ->with($paymentLineItemMocks, $decoratedProductMock)
            ->willReturn($decoratedProductMock);

        $paymentLineItemParams[PaymentLineItem::FIELD_PRODUCT] = $productToDecorate;
        $paymentLineItemToDecorate = new PaymentLineItem($paymentLineItemParams);

        $paymentLineItemParams[PaymentLineItem::FIELD_PRODUCT] = $decoratedProductMock;
        $expectedPaymentLineItem = new PaymentLineItem($paymentLineItemParams);

        $actualLineItem = $this->testedDecoratedProductLineItemFactory->createLineItemWithDecoratedProductByLineItem(
            $paymentLineItemMocks,
            $paymentLineItemToDecorate
        );

        static::assertEquals($expectedPaymentLineItem, $actualLineItem);
    }
}
