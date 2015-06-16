<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Unit\Entity;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProductItem;

class RequestProductItemTest extends AbstractTest
{
    public function testProperties()
    {
        $properties = [
            ['id', 123],
            ['requestProduct', new RequestProduct()],
            ['productUnit', new ProductUnit()],
            ['quantity', 11],
            ['price', new Price()],
        ];

        $this->assertPropertyAccessors(new RequestProductItem(), $properties);
    }

    public function testSetProductUnit()
    {
        $productUnit        = (new ProductUnit())->setCode('rfp-unit-code');
        $requestProductItem = new RequestProductItem();

        $this->assertNull($requestProductItem->getProductUnitCode());

        $requestProductItem->setProductUnit($productUnit);

        $this->assertEquals($productUnit->getCode(), $requestProductItem->getProductUnitCode());
    }

    public function testSetPrice()
    {
        $price = Price::create(22, 'EUR');

        $item = new RequestProductItem();
        $item->setPrice($price);

        $this->assertEquals($price, $item->getPrice());

        $this->assertEquals(22, $this->getProperty($item, 'value'));
        $this->assertEquals('EUR', $this->getProperty($item, 'currency'));
    }

    public function testLoadPrice()
    {
        $item = new RequestProductItem();

        $this->assertNull($item->getPrice());

        $this->setProperty($item, 'value', 10)->setProperty($item, 'currency', 'USD');

        $item->loadPrice();

        $this->assertEquals(Price::create(10, 'USD'), $item->getPrice());
    }

    public function testUpdatePrice()
    {
        $item = new RequestProductItem();
        $item->setPrice(Price::create(11, 'EUR'));

        $item->updatePrice();

        $this->assertEquals(11, $this->getProperty($item, 'value'));
        $this->assertEquals('EUR', $this->getProperty($item, 'currency'));
    }
}
