<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class BaseProductPriceTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        self::assertPropertyAccessors(
            new ProductPrice(),
            [
                ['id', 42],
                ['product', new Product()],
                ['unit', new ProductUnit()],
                ['priceList', new PriceList()],
                ['quantity', 12]
            ]
        );
    }

    public function testGetProductSku()
    {
        $product = new Product();
        $product->setSku('test');

        $price = new ProductPrice();
        $price->setProduct($product);

        self::assertEquals($product->getSku(), $price->getProductSku());
    }

    public function testSetGetPrice()
    {
        $productPrice = new ProductPrice();
        self::assertNull($productPrice->getPrice());

        $productPrice->updatePrice();
        self::assertNull($productPrice->getPrice());

        $value = 11;
        $currency = 'EUR';
        $productPrice->setPrice(Price::create($value, $currency));

        $price = $productPrice->getPrice();
        self::assertInstanceOf(Price::class, $price);
        self::assertEquals($value, $price->getValue());
        self::assertEquals($currency, $price->getCurrency());

        $price = Price::create(12, 'USD');
        $productPrice->setPrice($price);
        self::assertEquals($price, $productPrice->getPrice());

        $productPrice->updatePrice();
        self::assertEquals($price->getValue(), $productPrice->getPrice()->getValue());
        self::assertEquals($price->getCurrency(), $productPrice->getPrice()->getCurrency());
    }
}
