<?php

namespace Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\AccountUserRole;

class LoadAccountUserData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    const AUTH_USER = 'account_user@example.com';
    const AUTH_PW = 'account_user';

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
        $userManager = $this->container->get('oro_account_user.manager');

        $organization = $manager
            ->getRepository('OroOrganizationBundle:Organization')
            ->getFirst();

        $user = $manager
            ->getRepository('OroUserBundle:User')
            ->findOneBy([]);

        /** @var AccountUser $entity */
        $entity = $userManager->createUser();

        $role = $this->container
            ->get('doctrine')
            ->getManagerForClass('OroCustomerBundle:AccountUserRole')
            ->getRepository('OroCustomerBundle:AccountUserRole')
            ->findOneBy([], ['id' => Criteria::ASC]);

        $entity
            ->setFirstName('AccountUser')
            ->setLastName('AccountUser')
            ->setEmail(self::AUTH_USER)
            ->setOwner($user)
            ->setEnabled(true)
            ->setSalt('')
            ->setPlainPassword(self::AUTH_PW)
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->addRole($role);

        $userManager->updateUser($entity);
    }
}
