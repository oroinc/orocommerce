<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\OrderBundle\Entity\Order;

class LoadOrders extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderUsers',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createOrder($manager, 'simple_order');

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string        $name
     *
     * @return Order
     */
    protected function createOrder(ObjectManager $manager, $name)
    {
        $user = $this->getReference('order.simple_user');

        $order = new Order();
        $order->setOwner($user);
        $order->setOrganization($user->getOrganization());

        $manager->persist($order);
        $this->addReference($name, $order);

        return $order;
    }
}
