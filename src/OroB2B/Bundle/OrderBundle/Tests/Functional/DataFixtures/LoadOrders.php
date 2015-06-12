<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\OrderBundle\Entity\Order;

class LoadOrders extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createSimpleUser('order.simple_user');
        $this->createSimpleUser('order.simple_user2');
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

    /**
     * @param string $name
     */
    public function createSimpleUser($name)
    {
        $userManager = $this->container->get('oro_user.manager');

        $user = $userManager->createUser();
        $user->setUsername($name)
            ->setPlainPassword('simple_password')
            ->setEmail($name . '@example.com')
            ->setEnabled(true);

        $userManager->updateUser($user);

        $this->setReference($user->getUsername(), $user);
    }
}
