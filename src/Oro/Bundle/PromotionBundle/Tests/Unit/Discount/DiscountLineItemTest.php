<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\PromotionBundle\Discount\DiscountInformation;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class DiscountLineItemTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $lineItem = new DiscountLineItem();

        self::assertPropertyAccessors(
            $lineItem,
            [
                ['product', new Product()],
                ['price', Price::create(10, 'USD')],
                ['priceType', PriceTypeAwareInterface::PRICE_TYPE_UNIT],
                ['quantity', 10.5],
                ['productUnit', new ProductUnit()],
                ['subtotal', 42.2],
                ['sourceLineItem', new \stdClass()],
                ['subtotalAfterDiscounts', 41.1, false],
            ]
        );
    }

    public function testProductSku()
    {
        $product = new Product();
        $product->setSku('test1');

        $lineItem = new DiscountLineItem();
        $lineItem->setProductSku('test2');
        self::assertEquals('test2', $lineItem->getProductSku());

        $lineItem->setProduct($product);
        self::assertEquals('test1', $lineItem->getProductSku());
    }

    public function testProductUnitCode()
    {
        $unit = new ProductUnit();
        $unit->setCode('test1');

        $lineItem = new DiscountLineItem();
        $lineItem->setProductUnitCode('test2');
        self::assertEquals('test2', $lineItem->getProductUnitCode());

        $lineItem->setProductUnit($unit);
        self::assertEquals('test1', $lineItem->getProductUnitCode());
    }

    public function testDiscounts()
    {
        $lineItem = new DiscountLineItem();

        self::assertEmpty($lineItem->getDiscounts());
        /** @var DiscountInterface $discount */
        $discount = $this->createMock(DiscountInterface::class);

        $lineItem->addDiscount($discount);
        self::assertSame([$discount], $lineItem->getDiscounts());
    }

    public function testDiscountInformation()
    {
        $lineItem = new DiscountLineItem();

        self::assertEmpty($lineItem->getDiscountsInformation());
        /** @var DiscountInformation $info */
        $info = $this->createMock(DiscountInformation::class);

        $lineItem->addDiscountInformation($info);
        self::assertSame([$info], $lineItem->getDiscountsInformation());
    }

    public function testGetDiscountTotal()
    {
        /** @var DiscountInterface $discount */
        $discount = $this->createMock(DiscountInterface::class);
        $discountInformation1 = new DiscountInformation($discount, 10.5);
        $discountInformation2 = new DiscountInformation($discount, 20);

        $lineItem = new DiscountLineItem();
        $lineItem->addDiscountInformation($discountInformation1);
        $lineItem->addDiscountInformation($discountInformation2);

        self::assertEquals(30.5, $lineItem->getDiscountTotal());
    }

    public function testClone()
    {
        $product = new Product();
        $product->setSku('test1');

        $price = new Price();

        $lineItem = new DiscountLineItem();
        $lineItem->setProductSku('test2');
        $lineItem->setPrice($price);

        $productUnit = new ProductUnit();
        $lineItem->setProductUnit($productUnit);

        $clonedLineItem = clone $lineItem;
        self::assertEquals($lineItem, $clonedLineItem);
        self::assertSame($lineItem->getProduct(), $clonedLineItem->getProduct());
        self::assertNotSame($lineItem->getPrice(), $clonedLineItem->getPrice());
        self::assertNotSame($lineItem->getProductUnit(), $clonedLineItem->getProductUnit());
    }

    public function testGetSubtotalAfterDiscountsDefaultValue(): void
    {
        $lineItem = new DiscountLineItem();
        $lineItem->setSubtotal(1.1);

        self::assertEquals($lineItem->getSubtotal(), $lineItem->getSubtotalAfterDiscounts());
    }
}
