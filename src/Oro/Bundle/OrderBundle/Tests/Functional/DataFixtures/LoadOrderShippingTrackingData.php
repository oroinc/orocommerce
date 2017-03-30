<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderShippingTracking;

class LoadOrderShippingTrackingData extends AbstractFixture implements DependentFixtureInterface
{
    const ORDER_SHIPPING_TRACKING_1 = 'order_shipping_tracking.1';
    const ORDER_SHIPPING_TRACKING_2 = 'order_shipping_tracking.2';

    /**
     * @var array
     */
    protected $shippingTrackingItems = [
        self::ORDER_SHIPPING_TRACKING_1 => [
            'order' => LoadOrders::ORDER_1,
            'method' => 'method 1',
            'number' => 'number 1',
        ],
        self::ORDER_SHIPPING_TRACKING_2 => [
            'order' => LoadOrders::MY_ORDER,
            'method' => 'method 2',
            'number' => 'number 2',
        ],
    ];

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [LoadOrders::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->shippingTrackingItems as $name => $shippingTracking) {
            $this->createOrderShippingTracking($manager, $name, $shippingTracking);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string        $name
     * @param array         $shippingTracking
     * @return OrderShippingTracking
     */
    protected function createOrderShippingTracking(ObjectManager $manager, $name, array $shippingTracking)
    {
        /** @var Order $order */
        $order = $this->getReference($shippingTracking['order']);
        if (!$order) {
            throw new \RuntimeException(
                sprintf('Can\'t find order with code %s', $shippingTracking['order'])
            );
        }

        $orderShippingTracking = new OrderShippingTracking();
        $orderShippingTracking
            ->setOrder($order)
            ->setMethod($shippingTracking['method'])
            ->setNumber($shippingTracking['number']);

        $manager->persist($orderShippingTracking);

        $this->addReference($name, $orderShippingTracking);

        return $orderShippingTracking;
    }
}
