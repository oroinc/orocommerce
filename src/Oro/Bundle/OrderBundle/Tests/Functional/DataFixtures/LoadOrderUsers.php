<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
        /** @var Role $role */
        $role = $manager->getRepository(Role::class)->findOneBy(['role' => User::ROLE_DEFAULT]);

        $this->createOrderUser(self::ORDER_USER_1, $role);
        $this->createOrderUser(self::ORDER_USER_2, $role);

        $manager->flush();
    }

    /**
     * @param string $name
     * @param Role   $role
     */
    private function createOrderUser($name, Role $role)
    {
        $userManager = $this->container->get('oro_user.manager');

        $user = $userManager->createUser();
        $user->setUsername($name)
            ->setPlainPassword('simple_password')
            ->setFirstName($name . 'first_name')
            ->setLastName($name . 'last_name')
            ->setEmail($name . '@example.com')
            ->addUserRole($role)
            ->setEnabled(true);

        $userManager->updateUser($user, false);

        $this->setReference($user->getUsername(), $user);
    }
}
