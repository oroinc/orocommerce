<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Provider\OrderDuplicator;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Duplicator\Tests\Unit\DuplicatorTestCase;
use Oro\Component\Testing\ReflectionUtil;

class OrderDuplicatorTest extends DuplicatorTestCase
{
    public function testDuplicate(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 1);
        $order->setCreatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        $order->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        $order->setIdentifier('identifier');
        $order->setPoNumber('po number');
        $order->setParent(new Order());
        $order->setCustomer(new Customer());
        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 10);
        $lineItem->setCreatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        $lineItem->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        $lineItem->setProduct(new Product());
        $order->addLineItem($lineItem);
        $address = new OrderAddress();
        ReflectionUtil::setId($address, 100);
        $address->setCreated(new \DateTime('now', new \DateTimeZone('UTC')));
        $address->setUpdated(new \DateTime('now', new \DateTimeZone('UTC')));
        $address->setCountry(new Country('USA'));
        $order->setBillingAddress($address);

        $expectedOrder = new Order();
        $expectedOrder->setPoNumber($order->getPoNumber());
        $expectedOrder->setCustomer($order->getCustomer());
        $expectedLineItem = new OrderLineItem();
        $expectedLineItem->setProduct($lineItem->getProduct());
        $expectedOrder->addLineItem($expectedLineItem);
        $expectedAddress = new OrderAddress();
        $expectedAddress->setCountry($address->getCountry());
        $expectedOrder->setBillingAddress($expectedAddress);

        $orderDuplicator = new OrderDuplicator($this->createDuplicatorFactory());
        $result = $orderDuplicator->duplicate($order);

        self::assertEquals($expectedOrder, $result);
        self::assertSame($order->getCustomer(), $result->getCustomer());
        self::assertNotSame($order->getLineItems(), $result->getLineItems());
        self::assertNotSame($order->getLineItems()->first(), $result->getLineItems()->first());
        self::assertSame($order->getLineItems()->first()->getProduct(), $result->getLineItems()->first()->getProduct());
        self::assertNotSame($order->getBillingAddress(), $result->getBillingAddress());
        self::assertSame($order->getBillingAddress()->getCountry(), $result->getBillingAddress()->getCountry());
    }
}
