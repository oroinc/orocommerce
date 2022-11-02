<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\PromotionBundle\Discount\DisabledDiscountDecorator;
use Oro\Bundle\PromotionBundle\Discount\DisabledDiscountLineItemDecorator;
use Oro\Bundle\PromotionBundle\Discount\DiscountInformation;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DisabledDiscountLineItemDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DiscountLineItem|\PHPUnit\Framework\MockObject\MockObject
     */
    private $lineItem;

    /**
     * @var DisabledDiscountLineItemDecorator
     */
    private $decorator;

    protected function setUp(): void
    {
        $this->lineItem = $this->createMock(DiscountLineItem::class);
        $this->decorator = new DisabledDiscountLineItemDecorator($this->lineItem);
    }

    public function testGetPrice()
    {
        $price = Price::create(77, 'USD');

        $this->lineItem
            ->expects($this->once())
            ->method('getPrice')
            ->willReturn($price);

        static::assertEquals($price, $this->decorator->getPrice());
    }

    public function testSetPrice()
    {
        $price = Price::create(77, 'USD');

        $this->lineItem
            ->expects($this->once())
            ->method('setPrice')
            ->with($price);

        $this->decorator->setPrice($price);
    }

    public function testGetProduct()
    {
        $product = new Product();

        $this->lineItem
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);

        $this->decorator->getProduct();
    }

    public function testSetProduct()
    {
        $product = new Product();

        $this->lineItem
            ->expects($this->once())
            ->method('setProduct')
            ->with($product);

        $this->decorator->setProduct($product);
    }

    public function testGetProductSku()
    {
        $sku = 'TAG3';
        $this->lineItem
            ->expects($this->once())
            ->method('getProductSku')
            ->willReturn($sku);

        static::assertEquals($sku, $this->decorator->getProductSku());
    }

    public function testSetProductSku()
    {
        $sku = 'TAG3';
        $this->lineItem
            ->expects($this->once())
            ->method('setProductSku')
            ->with($sku);

        $this->decorator->setProductSku($sku);
    }

    public function testGetProductUnit()
    {
        $productUnit = new ProductUnit();

        $this->lineItem
            ->expects($this->once())
            ->method('getProductUnit')
            ->willReturn($productUnit);

        static::assertEquals($productUnit, $this->decorator->getProductUnit());
    }

    public function testSetProductUnit()
    {
        $productUnit = new ProductUnit();

        $this->lineItem
            ->expects($this->once())
            ->method('setProductUnit')
            ->with($productUnit);

        $this->decorator->setProductUnit($productUnit);
    }

    public function testGetProductUnitCode()
    {
        $productUnitCode = 'each';

        $this->lineItem
            ->expects($this->once())
            ->method('getProductUnitCode')
            ->willReturn($productUnitCode);

        static::assertEquals($productUnitCode, $this->decorator->getProductUnitCode());
    }

    public function testSetProductUnitCode()
    {
        $productUnitCode = 'each';

        $this->lineItem
            ->expects($this->once())
            ->method('setProductUnitCode')
            ->with($productUnitCode);

        $this->decorator->setProductUnitCode($productUnitCode);
    }

    public function testGetQuantity()
    {
        $quantity = 3.0;

        $this->lineItem
            ->expects($this->once())
            ->method('getQuantity')
            ->willReturn($quantity);

        static::assertEquals($quantity, $this->decorator->getQuantity());
    }

    public function testSetQuantity()
    {
        $quantity = 3.0;

        $this->lineItem
            ->expects($this->once())
            ->method('setQuantity')
            ->with($quantity);

        $this->decorator->setQuantity($quantity);
    }

    public function testGetSubtotal()
    {
        $subtotal = 7.5;

        $this->lineItem
            ->expects($this->once())
            ->method('getSubtotal')
            ->willReturn($subtotal);

        static::assertEquals($subtotal, $this->decorator->getSubtotal());
    }

    public function testSetSubtotal()
    {
        $subtotal = 7.5;

        $this->lineItem
            ->expects($this->once())
            ->method('setSubtotal')
            ->with($subtotal);

        $this->decorator->setSubtotal($subtotal);
    }

    public function testAddDiscount()
    {
        /** @var DiscountInterface|\PHPUnit\Framework\MockObject\MockObject $discount **/
        $discount = $this->createMock(DiscountInterface::class);

        $this->lineItem
            ->expects($this->once())
            ->method('addDiscount')
            ->with(new DisabledDiscountDecorator($discount));

        $this->decorator->addDiscount($discount);
    }

    public function testGetDiscounts()
    {
        /** @var DiscountInterface|\PHPUnit\Framework\MockObject\MockObject $discount **/
        $discount = $this->createMock(DiscountInterface::class);

        $this->lineItem
            ->expects($this->once())
            ->method('getDiscounts')
            ->willReturn([$discount]);

        static::assertEquals([$discount], $this->decorator->getDiscounts());
    }

    public function testGetPriceType()
    {
        $priceType = PriceTypeAwareInterface::PRICE_TYPE_UNIT;

        $this->lineItem
            ->expects($this->once())
            ->method('getPriceType')
            ->willReturn($priceType);

        static::assertEquals($priceType, $this->decorator->getPriceType());
    }

    public function testSetPriceType()
    {
        $priceType = PriceTypeAwareInterface::PRICE_TYPE_UNIT;

        $this->lineItem
            ->expects($this->once())
            ->method('setPriceType')
            ->with($priceType);

        $this->decorator->setPriceType($priceType);
    }

    public function testAddDiscountInformation()
    {
        /** @var DiscountInformation|\PHPUnit\Framework\MockObject\MockObject $discountInformation **/
        $discountInformation = $this->createMock(DiscountInformation::class);

        $this->lineItem
            ->expects($this->once())
            ->method('addDiscountInformation')
            ->with($discountInformation);

        $this->decorator->addDiscountInformation($discountInformation);
    }

    public function testGetDiscountsInformation()
    {
        /** @var DiscountInformation|\PHPUnit\Framework\MockObject\MockObject $discountInformation **/
        $discountInformation = $this->createMock(DiscountInformation::class);

        $this->lineItem
            ->expects($this->once())
            ->method('getDiscountsInformation')
            ->willReturn([$discountInformation]);

        static::assertEquals([$discountInformation], $this->decorator->getDiscountsInformation());
    }

    public function testGetDiscountTotal()
    {
        $total = 7.5;

        $this->lineItem
            ->expects($this->once())
            ->method('getDiscountTotal')
            ->willReturn($total);

        static::assertEquals($total, $this->decorator->getDiscountTotal());
    }

    public function testGetSourceLineItem()
    {
        $sourceLineItem = new OrderLineItem();

        $this->lineItem
            ->expects($this->once())
            ->method('getSourceLineItem')
            ->willReturn($sourceLineItem);

        static::assertEquals($sourceLineItem, $this->decorator->getSourceLineItem());
    }

    public function testSetSourceLineItem()
    {
        $sourceLineItem = new OrderLineItem();

        $this->lineItem
            ->expects($this->once())
            ->method('setSourceLineItem')
            ->with($sourceLineItem);

        $this->decorator->setSourceLineItem($sourceLineItem);
    }

    public function testSetSubtotalAfterDiscounts(): void
    {
        $subtotal = 7.5;

        $this->lineItem
            ->expects($this->once())
            ->method('setSubtotalAfterDiscounts')
            ->with($subtotal);

        $this->decorator->setSubtotalAfterDiscounts($subtotal);
    }

    public function testGetSubtotalAfterDiscounts(): void
    {
        $subtotal = 7.5;

        $this->lineItem
            ->expects($this->once())
            ->method('getSubtotalAfterDiscounts')
            ->willReturn($subtotal);

        self::assertEquals($subtotal, $this->decorator->getSubtotalAfterDiscounts());
    }
}
