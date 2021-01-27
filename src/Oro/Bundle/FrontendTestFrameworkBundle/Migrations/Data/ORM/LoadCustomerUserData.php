<?php

namespace Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadCustomerUserData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    const AUTH_USER = 'customer_user@example.com';
    const AUTH_PW = 'customer_user';

    /** @var ContainerInterface */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /** {@inheritdoc} */
    public function getDependencies()
    {
        return ['Oro\Bundle\TestFrameworkBundle\Migrations\Data\ORM\LoadUserData'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var BaseUserManager $userManager */
        $userManager = $this->container->get('oro_customer_user.manager');

        $organization = $manager
            ->getRepository('OroOrganizationBundle:Organization')
            ->getFirst();

        $user = $manager
            ->getRepository('OroUserBundle:User')
            ->findOneBy([]);

        /** @var CustomerUser $entity */
        $entity = $userManager->createUser();

        $role = $this->container
            ->get('doctrine')
            ->getManagerForClass('OroCustomerBundle:CustomerUserRole')
            ->getRepository('OroCustomerBundle:CustomerUserRole')
            ->findOneBy(['role' => 'ROLE_FRONTEND_ADMINISTRATOR']);

        $entity
            ->setFirstName('CustomerUser')
            ->setLastName('CustomerUser')
            ->setEmail(self::AUTH_USER)
            ->setOwner($user)
            ->setEnabled(true)
            ->setSalt('')
            ->setPlainPassword(self::AUTH_PW)
            ->setOrganization($organization)
            ->addRole($role);

        $userManager->updateUser($entity);
    }
}
