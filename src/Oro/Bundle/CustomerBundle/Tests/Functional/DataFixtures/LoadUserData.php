<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

class LoadUserData extends AbstractFixture implements ContainerAwareInterface
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
    public function load(ObjectManager $manager)
    {
        /** @var Organization $organization */
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();

        foreach ($this->users as $item) {
            /* @var $user User */
            $user = $this->userManager->createUser();
            $user->setUsername($item['username'])
                ->setEmail($item['email'])
                ->setFirstName($item['firstname'])
                ->setLastName($item['lastname'])
                ->setEnabled(true)
                ->setPlainPassword($item['email'])
                ->setOrganization($organization)
                ->addOrganization($organization);

            $this->userManager->updateUser($user);

            $this->setReference($user->getUsername(), $user);
        }
    }
}
