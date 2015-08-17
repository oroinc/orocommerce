<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Entity;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\OrderBundle\Entity\OrderProduct;
use OroB2B\Bundle\OrderBundle\Entity\OrderProductItem;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;

class OrderProductItemTest extends AbstractTest
{
    public function testProperties()
    {
        $properties = [
            ['id', 123],
            ['orderProduct', new OrderProduct()],
            ['quantity', 11],
            ['productUnit', new ProductUnit()],
            ['productUnitCode', 'unit-code'],
            ['price', new Price()],
            ['priceType', OrderProductItem::PRICE_TYPE_UNIT],
            ['fromQuote', true],
            ['quoteProductOffer', new QuoteProductOffer()]
        ];

        static::assertPropertyAccessors(new OrderProductItem(), $properties);
    }

    public function testEntityIdentifier()
    {
        $item = new OrderProductItem();
        $value = 321;
        $this->setProperty($item, 'id', $value);
        $this->assertEquals($value, $item->getEntityIdentifier());
    }

    public function testPostLoad()
    {
        $item = new OrderProductItem();

        static::assertNull($item->getPrice());

        $this->setProperty($item, 'value', 10)->setProperty($item, 'currency', 'USD');

        $item->postLoad();

        static::assertEquals(Price::create(10, 'USD'), $item->getPrice());
    }

    public function testPrePersist()
    {
        $item = new OrderProductItem();
        $this->assertNull($item->isFromQuote());

        $item->prePersist();
        $this->assertFalse($item->isFromQuote());
    }

    public function testUpdatePrice()
    {
        $item = new OrderProductItem();
        $item->setPrice(Price::create(11, 'EUR'));

        $item->updatePrice();

        static::assertEquals(11, $this->getProperty($item, 'value'));
        static::assertEquals('EUR', $this->getProperty($item, 'currency'));
    }

    public function testSetPrice()
    {
        $price = Price::create(22, 'EUR');

        $item = new OrderProductItem();
        $item->setPrice($price);

        static::assertEquals($price, $item->getPrice());

        static::assertEquals(22, $this->getProperty($item, 'value'));
        static::assertEquals('EUR', $this->getProperty($item, 'currency'));
    }

    public function testSetProductUnit()
    {
        $item = new OrderProductItem();

        static::assertNull($item->getProductUnitCode());

        $item->setProductUnit((new ProductUnit())->setCode('kg'));

        static::assertEquals('kg', $item->getProductUnitCode());
    }

    public function testGetPriceTypes()
    {
        static::assertEquals(
            [
                OrderProductItem::PRICE_TYPE_UNIT => 'unit',
                OrderProductItem::PRICE_TYPE_BUNDLED => 'bundled',
            ],
            OrderProductItem::getPriceTypes()
        );
    }
}
