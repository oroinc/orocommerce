<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;

class LoadAccountUserData extends AbstractFixture
{
    const FIRST_NAME = 'Grzegorz';
    const LAST_NAME = 'Brzeczyszczykiewicz';
    const EMAIL = 'grzegorz.brzeczyszczykiewicz@example.com';
    const PASSWORD = 'test';

    /**
     * @var array
     */
    protected $users = [
        [
            'first_name' => self::FIRST_NAME,
            'last_name' => self::LAST_NAME,
            'email' => self::EMAIL,
            'enabled' => true,
            'password' => self::PASSWORD
        ]
    ];

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->users as $user) {
            $entity = new AccountUser();
            $entity
                ->setFirstName($user['first_name'])
                ->setLastName($user['last_name'])
                ->setEmail($user['email'])
                ->setEnabled($user['enabled'])
                ->setPassword($user['password']);

            $manager->persist($entity);
        }

        $manager->flush();
    }
}
