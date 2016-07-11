<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData as UserData;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class LoadAccountUserData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use UserUtilityTrait;

    const FIRST_NAME = 'Grzegorz';
    const LAST_NAME = 'Brzeczyszczykiewicz';
    const EMAIL = 'grzegorz.brzeczyszczykiewicz@example.com';
    const PASSWORD = 'test';
    
    const LEVEL_1_FIRST_NAME = 'First';
    const LEVEL_1_LAST_NAME = 'Last';
    const LEVEL_1_EMAIL = 'other.user@test.com';
    const LEVEL_1_PASSWORD = 'pass';

    const LEVEL_1_1_FIRST_NAME = 'FirstName';
    const LEVEL_1_1_LAST_NAME = 'LastName';
    const LEVEL_1_1_EMAIL = 'second_account.user@test.com';
    const LEVEL_1_1_PASSWORD = 'pass';

    const ANONYMOUS_FIRST_NAME = 'FirstAccountUser';
    const ANONYMOUS_LAST_NAME = 'LastAccountUser';
    const ANONYMOUS_EMAIL = 'account.user2@test.com';
    const ANONYMOUS_PASSWORD = 'pass';

    const ORPHAN_FIRST_NAME = 'FirstOrphan';
    const ORPHAN_LAST_NAME = 'LastOrphan';
    const ORPHAN_EMAIL = 'orphan.user@test.com';
    const ORPHAN_PASSWORD = 'pass';

    const GROUP2_FIRST_NAME = 'FirstAccountUserGroup2';
    const GROUP2_LAST_NAME = 'LastAccountUserGroup2';
    const GROUP2_EMAIL = 'account.level_1.2@test.com';
    const GROUP2_PASSWORD = 'pass';

    /** @var ContainerInterface */
    protected $container;

    /**
     * @var array
     */
    protected static $users = [
        [
            'first_name' => self::FIRST_NAME,
            'last_name' => self::LAST_NAME,
            'email' => self::EMAIL,
            'enabled' => true,
            'password' => self::PASSWORD,
            'account' => 'account.level_1'
        ],
        [
            'first_name' => self::LEVEL_1_FIRST_NAME,
            'last_name' => self::LEVEL_1_LAST_NAME,
            'email' => self::LEVEL_1_EMAIL,
            'enabled' => true,
            'password' => self::LEVEL_1_PASSWORD,
            'account' => 'account.level_1'
        ],
        [
            'first_name' => self::LEVEL_1_1_FIRST_NAME,
            'last_name' => self::LEVEL_1_1_LAST_NAME,
            'email' => self::LEVEL_1_1_EMAIL,
            'enabled' => true,
            'password' => self::LEVEL_1_1_PASSWORD,
            'account' => 'account.level_1.1'
        ],
        [
            'first_name' => self::ORPHAN_FIRST_NAME,
            'last_name' => self::ORPHAN_LAST_NAME,
            'email' => self::ORPHAN_EMAIL,
            'enabled' => true,
            'password' => self::ORPHAN_PASSWORD,
            'account' => 'account.orphan'
        ],
        [
            'first_name' => 'FirstAccountUser',
            'last_name' => 'LastAccountUser',
            'email' => 'account.user2@test.com',
            'enabled' => true,
            'password' => 'pass'
        ],
        [
            'first_name' => self::GROUP2_FIRST_NAME,
            'last_name' => self::GROUP2_LAST_NAME,
            'email' => self::GROUP2_EMAIL,
            'password' => self::GROUP2_PASSWORD,
            'enabled' => true,
            'account' => 'account.level_1.2'
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /** @var BaseUserManager $userManager */
        $userManager = $this->container->get('orob2b_account_user.manager');
        $owner = $this->getFirstUser($manager);
        $role = $manager->getRepository('OroB2BAccountBundle:AccountUserRole')->findOneBy([]);
        foreach (static::$users as $user) {
            if (isset($user['account'])) {
                /** @var Account $account */
                $account = $this->getReference($user['account']);
            } else {
                $accountUser = $manager->getRepository('OroB2BAccountBundle:AccountUser')
                    ->findOneBy(['username' => UserData::AUTH_USER]);
                $account = $accountUser->getAccount();
            }
            $entity = new AccountUser();
            $entity
                ->setAccount($account)
                ->setOwner($owner)
                ->setFirstName($user['first_name'])
                ->setLastName($user['last_name'])
                ->setEmail($user['email'])
                ->setEnabled($user['enabled'])
                ->setOrganization($account->getOrganization())
                ->addOrganization($account->getOrganization())
                ->addRole($role)
                ->setPlainPassword($user['password']);

            $this->setReference($entity->getEmail(), $entity);

            $userManager->updateUser($entity);
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
