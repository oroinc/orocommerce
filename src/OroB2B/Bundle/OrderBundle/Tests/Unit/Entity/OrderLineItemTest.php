<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Entity;

use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class OrderLineItemTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', '123'],
            ['order', new Order()],
            ['product', new Product()],
            ['productSku', '1234'],
            ['freeFormProduct', 'Services'],
            ['quantity', 42],
            ['productUnit', new ProductUnit()],
            ['productUnitCode', 'item'],
            ['value', 42],
            ['currency', 'USD'],
            ['price', Price::create(42, 'USD')],
            ['priceType', 10],
            ['shipBy', $now],
            ['fromExternalSource', true],
            ['comment', 'The answer is 42'],
        ];

        $entity = new OrderLineItem();
        $this->assertPropertyAccessors($entity, $properties);
    }

    public function testPostLoad()
    {
        $entity = new OrderLineItem();
        $entity->setValue(42);
        $entity->setCurrency('USD');
        $this->assertEmpty($entity->getPrice());
        $entity->postLoad();
        $this->assertEquals(Price::create(42, 'USD'), $entity->getPrice());
    }

    public function testPrePersist()
    {
        $entity = new OrderLineItem();
        $entity->setPrice(Price::create(42, 'USD'));
        $this->assertEquals(42, $entity->getValue());
        $this->assertEquals('USD', $entity->getCurrency());

        $entity->getPrice()->setValue(84);
        $entity->getPrice()->setCurrency('EUR');

        $this->assertEmpty($entity->getProductSku());
        $this->assertEmpty($entity->getProductUnitCode());

        $entity->setProduct((new Product())->setSku('SKU'));
        $entity->setProductUnit((new ProductUnit())->setCode('kg'));

        $entity->prePersist();
        $this->assertEquals(84, $entity->getValue());
        $this->assertEquals('EUR', $entity->getCurrency());
        $this->assertEquals('SKU', $entity->getProductSku());
        $this->assertEquals('kg', $entity->getProductUnitCode());
    }
}
