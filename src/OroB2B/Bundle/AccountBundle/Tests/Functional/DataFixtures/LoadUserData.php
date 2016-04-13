<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

class LoadUserData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    const USER1 = 'admin-user1';
    const USER2 = 'admin-user2';

    /** @var array */
    protected $users = [
        [
            'email' => 'admin-user1@example.com',
            'username' => self::USER1,
            'firstname' => 'AdminUser1FN',
            'lastname' => 'AdminUser1LN',
        ],
        [
            'email' => 'admin-user2@example.com',
            'username' => self::USER2,
            'firstname' => 'AdminUser2FN',
            'lastname' => 'AdminUser2LN',
        ],
    ];

    /** @var UserManager */
    protected $userManager;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->userManager = $container->get('oro_user.manager');
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Organization $organization */
        $organization = $this->getReference('default_organization');

        /** @var BusinessUnit $businessUnit */
        $businessUnit = $this->getReference('default_business_unit');

        foreach ($this->users as $item) {
            /* @var $user User */
            $user = $this->userManager->createUser();
            $user->setUsername($item['username'])
                ->setEmail($item['email'])
                ->setFirstName($item['firstname'])
                ->setLastName($item['lastname'])
                ->setOwner($businessUnit)
                ->addBusinessUnit($businessUnit)
                ->setEnabled(true)
                ->setPlainPassword($item['email'])
                ->setOrganization($organization)
                ->addOrganization($organization);

            $this->userManager->updateUser($user);

            $this->setReference($user->getUsername(), $user);
        }
    }
}
