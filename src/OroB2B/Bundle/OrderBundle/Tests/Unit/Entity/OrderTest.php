<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderProduct;
use OroB2B\Bundle\SaleBundle\Entity\Quote;

class OrderTest extends AbstractTest
{
    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', '123'],
            ['identifier', 'ORD-123456'],
            ['owner', new User()],
            ['accountUser', new AccountUser()],
            ['account', new Customer()],
            ['organization', new Organization()],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
            ['quote', new Quote()],
        ];

        static::assertPropertyAccessors(new Order(), $properties);

        static::assertPropertyCollections(new Order(), [
            ['orderProducts', new OrderProduct()],
        ]);
    }

    public function testToString()
    {
        $order = new Order();

        $this->assertSame('', (string)$order);

        $order->setSku(123);

        $this->assertSame('123', (string)$order);
    }

    public function testPrePersist()
    {
        $order = new Order();

        static::assertNull($order->getCreatedAt());
        static::assertNull($order->getUpdatedAt());

        $order->prePersist();

        static::assertInstanceOf('\DateTime', $order->getCreatedAt());
        static::assertInstanceOf('\DateTime', $order->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $order = new Order();

        static::assertNull($order->getUpdatedAt());

        $order->preUpdate();

        static::assertInstanceOf('\DateTime', $order->getUpdatedAt());
    }

    public function testAddOrderProduct()
    {
        $order          = new Order();
        $orderProduct   = new OrderProduct();

        static::assertNull($orderProduct->getOrder());

        $order->addOrderProduct($orderProduct);

        static::assertEquals($order, $orderProduct->getOrder());
    }
}
