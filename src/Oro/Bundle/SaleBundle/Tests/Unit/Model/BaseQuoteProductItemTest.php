<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Model;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Model\BaseQuoteProductItem;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class BaseQuoteProductItemTest extends \PHPUnit\Framework\TestCase
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

        self::assertEquals($value, ReflectionUtil::getPropertyValue($item, 'value'));
        self::assertEquals($currency, ReflectionUtil::getPropertyValue($item, 'currency'));
    }

    public function testGetEntityIdentifier()
    {
        $item = new BaseQuoteProductItem();

        $value = 321;
        ReflectionUtil::setId($item, $value);
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
