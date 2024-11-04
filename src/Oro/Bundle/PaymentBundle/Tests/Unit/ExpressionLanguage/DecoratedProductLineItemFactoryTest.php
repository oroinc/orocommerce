<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\ExpressionLanguage;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PaymentBundle\Context\PaymentKitItemLineItem;
use Oro\Bundle\PaymentBundle\ExpressionLanguage\DecoratedProductLineItemFactory;
use Oro\Bundle\PaymentBundle\Tests\Unit\Context\PaymentLineItemTrait;
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
    use PaymentLineItemTrait;

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

    public function testCreatePaymentLineItemWithDecoratedProduct(): void
    {
        $product1 = new ProductStub();
        $product1->setId(1001);
        $product2 = new ProductStub();
        $product2->setId(2002);
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

        $paymentKitItemLineItemToDecorate = (new PaymentKitItemLineItem(
            $productUnit,
            $quantity,
            $productHolder
        ))
            ->setProductUnitCode($unitCode)
            ->setProduct($product2)
            ->setProductSku($product2->getSku())
            ->setPrice($price)
            ->setKitItem($kitItem)
            ->setSortOrder($sortOrder);

        $paymentKitItemLineItemsToDecorate = new ArrayCollection([$paymentKitItemLineItemToDecorate]);

        $paymentKitItemLineItemWithDecoratedProduct = (new PaymentKitItemLineItem(
            $productUnit,
            $quantity,
            $productHolder
        ))
            ->setProductUnitCode($unitCode)
            ->setProduct($decoratedProduct2Mock)
            ->setProductSku($product2->getSku())
            ->setPrice($price)
            ->setKitItem($kitItem)
            ->setSortOrder($sortOrder);

        $kitItemLineItemsWithDecoratedProduct = new ArrayCollection([
            $paymentKitItemLineItemWithDecoratedProduct,
        ]);

        $paymentLineItemToDecorate = $this->getPaymentLineItem(quantity: 20, unitCode: 'each')
            ->setProduct($product1)
            ->setProductSku('sku')
            ->setPrice($this->createMock(Price::class))
            ->setKitItemLineItems($paymentKitItemLineItemsToDecorate)
            ->setChecksum('checksum_1');

        $expectedPaymentLineItem = $this->getPaymentLineItem(quantity: 20, unitCode: 'each')
            ->setProduct($decoratedProduct1Mock)
            ->setProductSku('sku')
            ->setPrice($this->createMock(Price::class))
            ->setKitItemLineItems($kitItemLineItemsWithDecoratedProduct)
            ->setChecksum('checksum_1');

        $actualLineItem = $this->testedDecoratedProductLineItemFactory->createPaymentLineItemWithDecoratedProduct(
            $paymentLineItemToDecorate,
            $products
        );

        self::assertEquals($expectedPaymentLineItem, $actualLineItem);
    }
}
