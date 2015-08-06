<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

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
        ],
        [
            'first_name' => 'First',
            'last_name' => 'Last',
            'email' => 'other.user@test.com',
            'enabled' => true,
            'password' => 'pass'
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

            $this->setReference($entity->getEmail(), $entity);

            $manager->persist($entity);
        }

        $manager->flush();
    }
}
