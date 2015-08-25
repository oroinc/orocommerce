<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

class LoadOrderUsers extends AbstractFixture implements ContainerAwareInterface
{
    const ORDER_USER_1 = 'order.simple_user';
    const ORDER_USER_2 = 'order.simple_user2';

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
        $this->createOrderUser(self::ORDER_USER_1);
        $this->createOrderUser(self::ORDER_USER_2);

        $manager->flush();
    }

    /**
     * @param string $name
     */
    public function createOrderUser($name)
    {
        $userManager = $this->container->get('oro_user.manager');

        $user = $userManager->createUser();
        $user->setUsername($name)
            ->setPlainPassword('simple_password')
            ->setFirstName($name . 'first_name')
            ->setLastName($name . 'last_name')
            ->setEmail($name . '@example.com')
            ->setEnabled(true);

        $userManager->updateUser($user);

        $this->setReference($user->getUsername(), $user);
    }
}
