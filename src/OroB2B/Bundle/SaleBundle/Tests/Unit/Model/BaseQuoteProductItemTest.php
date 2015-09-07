<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Model;

use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Model\BaseQuoteProductItem;

class BaseQuoteProductItemTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', 123],
            ['quoteProduct', new QuoteProduct()],
            ['quantity', 1.1],
            ['productUnit', new ProductUnit()],
            ['productUnitCode', 'prodCode'],
        ];

        static::assertPropertyAccessors(new BaseQuoteProductItem(), $properties);
    }

    public function testPrice()
    {
        $value = 1.1;
        $currency = 'USD';

        $price = new Price();
        $price->setCurrency($currency);
        $price->setValue($value);

        $item = new BaseQuoteProductItem();
        $item->setPrice($price);
        static::assertSame($price, $item->getPrice());

        $item->postLoad();

        static::assertNotSame($price, $item->getPrice());
        static::assertEquals($price, $item->getPrice());

        $item->updatePrice();

        $reflection = new \ReflectionProperty(get_class($item), 'value');
        $reflection->setAccessible(true);
        static::assertEquals($value, $reflection->getValue($item));

        $reflection = new \ReflectionProperty(get_class($item), 'currency');
        $reflection->setAccessible(true);
        static::assertEquals($currency, $reflection->getValue($item));
    }

    public function testGetEntityIdentifier()
    {
        $item = new BaseQuoteProductItem();
        $value = 321;

        $reflection = new \ReflectionProperty(get_class($item), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($item, $value);

        static::assertEquals($value, $item->getEntityIdentifier());
    }

    public function testGetProductHolder()
    {
        $item = new BaseQuoteProductItem();
        $value = new QuoteProduct();
        $item->setQuoteProduct($value);
        static::assertSame($value, $item->getProductHolder());
    }
}
