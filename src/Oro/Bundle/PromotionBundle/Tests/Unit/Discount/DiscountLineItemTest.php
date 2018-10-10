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

class DiscountLineItemTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $lineItem = new DiscountLineItem();

        $this->assertPropertyAccessors(
            $lineItem,
            [
                ['product', new Product()],
                ['price', Price::create(10, 'USD')],
                ['priceType', PriceTypeAwareInterface::PRICE_TYPE_UNIT],
                ['quantity', 10.5],
                ['productUnit', new ProductUnit()],
                ['subtotal', 42.2],
                ['sourceLineItem', new \stdClass()]
            ]
        );
    }

    public function testProductSku()
    {
        $product = new Product();
        $product->setSku('test1');

        $lineItem = new DiscountLineItem();
        $lineItem->setProductSku('test2');
        $this->assertEquals('test2', $lineItem->getProductSku());

        $lineItem->setProduct($product);
        $this->assertEquals('test1', $lineItem->getProductSku());
    }

    public function testProductUnitCode()
    {
        $unit = new ProductUnit();
        $unit->setCode('test1');

        $lineItem = new DiscountLineItem();
        $lineItem->setProductUnitCode('test2');
        $this->assertEquals('test2', $lineItem->getProductUnitCode());

        $lineItem->setProductUnit($unit);
        $this->assertEquals('test1', $lineItem->getProductUnitCode());
    }

    public function testDiscounts()
    {
        $lineItem = new DiscountLineItem();

        $this->assertEmpty($lineItem->getDiscounts());
        /** @var DiscountInterface $discount */
        $discount = $this->createMock(DiscountInterface::class);

        $lineItem->addDiscount($discount);
        $this->assertSame([$discount], $lineItem->getDiscounts());
    }

    public function testDiscountInformation()
    {
        $lineItem = new DiscountLineItem();

        $this->assertEmpty($lineItem->getDiscountsInformation());
        /** @var DiscountInformation $info */
        $info = $this->createMock(DiscountInformation::class);

        $lineItem->addDiscountInformation($info);
        $this->assertSame([$info], $lineItem->getDiscountsInformation());
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

        $this->assertEquals(30.5, $lineItem->getDiscountTotal());
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
        $this->assertEquals($lineItem, $clonedLineItem);
        $this->assertSame($lineItem->getProduct(), $clonedLineItem->getProduct());
        $this->assertNotSame($lineItem->getPrice(), $clonedLineItem->getPrice());
        $this->assertNotSame($lineItem->getProductUnit(), $clonedLineItem->getProductUnit());
    }
}
