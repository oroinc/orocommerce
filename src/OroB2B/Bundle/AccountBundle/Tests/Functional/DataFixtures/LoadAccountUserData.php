<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

use Oro\Component\Testing\Fixtures\LoadAccountUserData as UserData;

class LoadAccountUserData extends AbstractFixture implements DependentFixtureInterface
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
            'password' => self::PASSWORD,
            'account' => 'account.level_1'
        ],
        [
            'first_name' => 'First',
            'last_name' => 'Last',
            'email' => 'other.user@test.com',
            'enabled' => true,
            'password' => 'pass',
            'account' => 'account.level_1'
        ],
        [
            'first_name' => 'FirstName',
            'last_name' => 'LastName',
            'email' => 'second_account.user@test.com',
            'enabled' => true,
            'password' => 'pass',
            'account' => 'account.level_1.1'
        ],
        [
            'first_name' => 'FirstOrphan',
            'last_name' => 'LastOrphan',
            'email' => 'orphan.user@test.com',
            'enabled' => true,
            'password' => 'pass',
            'account' => 'account.orphan'
        ],
        [
            'first_name' => 'FirstAccountUser',
            'last_name' => 'LastAccountUser',
            'email' => 'account.user2@test.com',
            'enabled' => true,
            'password' => 'pass'
        ],
    ];

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->users as $user) {
            if (isset($user['account'])) {
                /** @var Account $account */
                $account = $this->getReference($user['account']);
            } else {
                $accountUser = $manager->getRepository('OroB2BAccountBundle:AccountUser')
                    ->findOneBy(['username' => UserData::AUTH_USER]);
                $account = $accountUser->getAccount();
            }
            $role = $manager->getRepository('OroB2BAccountBundle:AccountUserRole')->findOneBy([]);
            $entity = new AccountUser();
            $entity
                ->setAccount($account)
                ->setFirstName($user['first_name'])
                ->setLastName($user['last_name'])
                ->setEmail($user['email'])
                ->setEnabled($user['enabled'])
                ->setOrganization($account->getOrganization())
                ->addRole($role)
                ->setPassword($user['password']);

            $this->setReference($entity->getEmail(), $entity);

            $manager->persist($entity);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts'
        ];
    }
}
