<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Entity;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderProduct;
use OroB2B\Bundle\OrderBundle\Entity\OrderProductItem;

class OrderProductTest extends AbstractTest
{
    public function testProperties()
    {
        $properties = [
            ['id', 123],
            ['order', new Order()],
            ['product', new Product()],
            ['productSku', 'sku'],
            ['comment', 'Seller notes'],
        ];

        static::assertPropertyAccessors(new OrderProduct(), $properties);

        static::assertPropertyCollections(new OrderProduct(), [
            ['orderProductItems', new OrderProductItem()],
        ]);
    }

    public function testEntityIdentifier()
    {
        $product = new OrderProduct();
        $value = 321;
        $this->setProperty($product, 'id', $value);
        $this->assertEquals($value, $product->getEntityIdentifier());
    }

    public function testSetProduct()
    {
        $product = new OrderProduct();

        static::assertNull($product->getProductSku());

        $product->setProduct((new Product)->setSku('test-sku'));

        static::assertEquals('test-sku', $product->getProductSku());
    }

    public function testAddOrderProductItem()
    {
        $orderProduct = new OrderProduct();
        $orderProductItem = new OrderProductItem();

        static::assertNull($orderProductItem->getOrderProduct());

        $orderProduct->addOrderProductItem($orderProductItem);

        static::assertEquals($orderProduct, $orderProductItem->getOrderProduct());
    }
}
