<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

class LoadUserData extends AbstractFixture
{
    const USER1 = 'sale-user1';
    const USER2 = 'sale-user2';

    /**
     * @var array
     */
    protected $users = [
        [
            'email'     => 'sale-user1@example.com',
            'username'  => self::USER1,
            'password'  => self::USER1,
            'firstname' => 'SaleUser1FN',
            'lastname'  => 'SaleUser1LN',
        ],
        [
            'email'     => 'sale-user2@example.com',
            'username'  => self::USER2,
            'password'  => self::USER2,
            'firstname' => 'SaleUser2FN',
            'lastname'  => 'SaleUser2LN',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /* @var $userManager UserManager */
        $userManager = $this->container->get('oro_user.manager');

        $defaultUser    = $this->getUser($manager);

        $businessUnit   = $defaultUser->getOwner();
        $organization   = $defaultUser->getOrganization();

        foreach ($this->users as $item) {
            /* @var $user User */
            $user = $userManager->createUser();

            $user
                ->setEmail($item['email'])
                ->setUsername($item['username'])
                ->setPlainPassword($item['password'])
                ->setFirstname($item['firstname'])
                ->setLastname($item['lastname'])

                ->setEnabled(true)

                ->setOwner($businessUnit)
                ->setOrganization($organization)
            ;

            $userManager->updateUser($user);

            $this->setReference($user->getUsername(), $user);
        }
    }
}
