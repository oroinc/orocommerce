<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Context\LineItem\Builder\Basic;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PaymentBundle\Context\LineItem\Builder\Basic\BasicPaymentLineItemBuilder;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;

class BasicPaymentLineItemBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var Price|\PHPUnit\Framework\MockObject\MockObject */
    private $price;

    /** @var ProductUnit|\PHPUnit\Framework\MockObject\MockObject */
    private $productUnit;

    /** @var ProductHolderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productHolder;

    /** @var Product|\PHPUnit\Framework\MockObject\MockObject */
    private $product;

    protected function setUp(): void
    {
        $this->price = $this->createMock(Price::class);
        $this->productUnit = $this->createMock(ProductUnit::class);
        $this->productHolder = $this->createMock(ProductHolderInterface::class);
        $this->product = $this->createMock(Product::class);
    }

    public function testFullBuild()
    {
        $unitCode = 'someCode';
        $quantity = 15;
        $productSku = 'someSku';
        $entityIdentifier = 'someId';

        $this->productHolder->expects(self::once())
            ->method('getEntityIdentifier')
            ->willReturn($entityIdentifier);

        $builder = new BasicPaymentLineItemBuilder(
            $this->productUnit,
            $unitCode,
            $quantity,
            $this->productHolder
        );

        $builder
            ->setPrice($this->price)
            ->setProduct($this->product)
            ->setProductSku($productSku);

        $paymentLineItem = $builder->getResult();

        $expectedPaymentLineItem = new PaymentLineItem([
            PaymentLineItem::FIELD_PRICE => $this->price,
            PaymentLineItem::FIELD_PRODUCT_UNIT => $this->productUnit,
            PaymentLineItem::FIELD_PRODUCT_UNIT_CODE => $unitCode,
            PaymentLineItem::FIELD_QUANTITY => $quantity,
            PaymentLineItem::FIELD_PRODUCT_HOLDER => $this->productHolder,
            PaymentLineItem::FIELD_PRODUCT => $this->product,
            PaymentLineItem::FIELD_PRODUCT_SKU => $productSku,
            PaymentLineItem::FIELD_ENTITY_IDENTIFIER => $entityIdentifier
        ]);

        self::assertEquals($expectedPaymentLineItem, $paymentLineItem);
    }

    public function testOptionalBuild()
    {
        $unitCode = 'someCode';
        $quantity = 15;
        $entityIdentifier = 'someId';

        $this->productHolder->expects(self::once())
            ->method('getEntityIdentifier')
            ->willReturn($entityIdentifier);

        $builder = new BasicPaymentLineItemBuilder(
            $this->productUnit,
            $unitCode,
            $quantity,
            $this->productHolder
        );

        $paymentLineItem = $builder->getResult();

        $expectedPaymentLineItem = new PaymentLineItem([
            PaymentLineItem::FIELD_PRODUCT_UNIT => $this->productUnit,
            PaymentLineItem::FIELD_PRODUCT_UNIT_CODE => $unitCode,
            PaymentLineItem::FIELD_QUANTITY => $quantity,
            PaymentLineItem::FIELD_PRODUCT_HOLDER => $this->productHolder,
            PaymentLineItem::FIELD_ENTITY_IDENTIFIER => $entityIdentifier
        ]);

        self::assertEquals($expectedPaymentLineItem, $paymentLineItem);
    }
}
