<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

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
            'product' => LoadProductData::PRODUCT_3
        ],
        self::ORDER_ITEM_2 => [
            'quantity' => 6,
            'price' => '5.55',
            'product' => LoadProductData::PRODUCT_1
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders',
            'Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData'
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
            $manager->getRepository(Country::class)->find(LoadTaxJurisdictions::COUNTRY_US)
        );
        $address->setRegion(
            $manager->getRepository(Region::class)->find(LoadTaxJurisdictions::STATE_US_NY)
        );
        $order
            ->setBillingAddress($address)
            ->setShippingAddress(clone $address);

        foreach ($this->orderLineItems as $name => $orderLineItem) {
            $orderLineItemEntity = $this->createOrderLineItem($manager, $order, $orderLineItem);
            $order->addLineItem($orderLineItemEntity);
            $this->setReference($name, $orderLineItemEntity);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param Order $order
     * @param array $orderLineItemData
     * @return OrderLineItem
     */
    protected function createOrderLineItem(ObjectManager $manager, Order $order, array $orderLineItemData)
    {
        $orderLineItem = new OrderLineItem();
        $orderLineItem
            ->setProduct($this->getReference($orderLineItemData['product']))
            ->setQuantity($orderLineItemData['quantity'])
            ->setPrice(Price::create($orderLineItemData['price'], 'USD'));
        $order->addLineItem($orderLineItem);

        $manager->persist($orderLineItem);

        return $orderLineItem;
    }
}
