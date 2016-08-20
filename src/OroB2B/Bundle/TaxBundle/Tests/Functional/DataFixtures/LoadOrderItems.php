<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;

class LoadOrderItems extends AbstractFixture implements DependentFixtureInterface
{
    const ORDER_ITEM_1 = 'simple_order_item_1';
    const ORDER_ITEM_2 = 'simple_order_item_2';

    /**
     * @var array
     */
    protected $orderLineItems = [
        self::ORDER_ITEM_1 => [
            'quantity' => 5,
            'price' => '15.99',
        ],
        self::ORDER_ITEM_2 => [
            'quantity' => 6,
            'price' => '5.55',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $address = new OrderAddress();
        $address->setCountry(
            $manager->getRepository('OroAddressBundle:Country')->find(LoadTaxJurisdictions::COUNTRY_US)
        );
        $address->setRegion(
            $manager->getRepository('OroAddressBundle:Region')->find(LoadTaxJurisdictions::STATE_US_NY)
        );
        $order
            ->setBillingAddress($address)
            ->setShippingAddress($address);

        foreach ($this->orderLineItems as $name => $orderLineItem) {
            $this->setReference(
                $name,
                $this->createOrderLineItem($manager, $order, $orderLineItem, $name)
            );
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param Order $order
     * @param array $orderLineItemData
     * @param string $name
     * @return OrderLineItem
     */
    protected function createOrderLineItem(ObjectManager $manager, Order $order, array $orderLineItemData, $name)
    {
        $orderLineItem = new OrderLineItem();
        $orderLineItem
            ->setProductSku($name)
            ->setQuantity($orderLineItemData['quantity'])
            ->setPrice(Price::create($orderLineItemData['price'], 'USD'));
        $order->addLineItem($orderLineItem);

        $manager->persist($order);

        return $orderLineItem;
    }
}
