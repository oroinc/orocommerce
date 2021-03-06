<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Entity;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class RequestProductItemTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', 123],
            ['requestProduct', new RequestProduct()],
            ['quantity', 11],
            ['productUnit', new ProductUnit()],
            ['productUnitCode', 'unit-code'],
            ['price', new Price()],
        ];

        static::assertPropertyAccessors(new RequestProductItem(), $properties);
    }

    public function testGetEntityIdentifier()
    {
        $item = new RequestProductItem();
        $value = 321;
        ReflectionUtil::setId($item, $value);
        $this->assertSame($value, $item->getEntityIdentifier());
    }

    public function testGetProductHolder()
    {
        $requestProduct = new RequestProduct();

        $item = new RequestProductItem();
        $item->setRequestProduct($requestProduct);

        $this->assertSame($requestProduct, $item->getProductHolder());
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

        $this->assertEquals(22, ReflectionUtil::getPropertyValue($item, 'value'));
        $this->assertEquals('EUR', ReflectionUtil::getPropertyValue($item, 'currency'));
    }

    public function testLoadPrice()
    {
        $item = new RequestProductItem();

        $this->assertNull($item->getPrice());

        ReflectionUtil::setPropertyValue($item, 'value', 10);
        ReflectionUtil::setPropertyValue($item, 'currency', 'USD');

        $item->loadPrice();

        $this->assertEquals(Price::create(10, 'USD'), $item->getPrice());
    }

    public function testUpdatePrice()
    {
        $item = new RequestProductItem();
        $item->setPrice(Price::create(11, 'EUR'));

        $item->updatePrice();

        $this->assertEquals(11, ReflectionUtil::getPropertyValue($item, 'value'));
        $this->assertEquals('EUR', ReflectionUtil::getPropertyValue($item, 'currency'));
    }
}
