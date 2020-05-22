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
        static::assertPropertyAccessors(
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

        static::assertEquals($product->getSku(), $price->getProductSku());
    }

    public function testSetGetPrice()
    {
        $productPrice = new class() extends ProductPrice {
            public function xsetValueAndCurrency(float $value, string $currency): void
            {
                $this->value = $value;
                $this->currency = $currency;
            }
        };
        static::assertNull($productPrice->getPrice());

        $productPrice->updatePrice();
        static::assertNull($productPrice->getPrice());

        $value = 11;
        $currency = 'EUR';
        $productPrice->xsetValueAndCurrency($value, $currency);

        $price = $productPrice->getPrice();
        static::assertInstanceOf(Price::class, $price);
        static::assertEquals($value, $price->getValue());
        static::assertEquals($currency, $price->getCurrency());

        $price = Price::create(12, 'USD');
        $productPrice->setPrice($price);
        static::assertEquals($price, $productPrice->getPrice());

        $productPrice->updatePrice();
        static::assertEquals($price->getValue(), $productPrice->getPrice()->getValue());
        static::assertEquals($price->getCurrency(), $productPrice->getPrice()->getCurrency());
    }
}
