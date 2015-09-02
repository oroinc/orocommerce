<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\UserBundle\Entity\BaseUserManager;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountUserRoleRepository;

class LoadAccountUsersData extends AbstractFixture implements DependentFixtureInterface
{
    const ADMINISTRATOR = 'ROLE_FRONTEND_ADMINISTRATOR';
    const BUYER         = 'ROLE_FRONTEND_BUYER';

    const USER_NAME      = 'John';
    const USER_LAST_NAME = 'Doe';
    const USER_EMAIL     = 'user@example.com';
    const USER_PASSWORD  = '123123';

    const SUB_ACCOUNT_USER_EMAIL = 'sub_account@example.com';
    const SUB_ACCOUNT_USER_PASSWORD ='147147';

    const SAME_ACCOUNT_USER_EMAIL    = 'same_account@example.com';
    const SAME_ACCOUNT_USER_PASSWORD = '456456';

    const NOT_SAME_ACCOUNT_USER_EMAIL    = 'not_same_account@example.com';
    const NOT_SAME_ACCOUNT_USER_PASSWORD = '789789';

    /** @var ContainerInterface */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts'
        ];
    }

    /**
     * @var array
     */
    protected $users = [
        [
            'first_name' => self::USER_NAME,
            'last_name' => self::USER_LAST_NAME,
            'email' => self::USER_EMAIL,
            'password' => self::USER_PASSWORD,
            'enabled' => true,
            'confirmed' => true,
            'account' => 'account.level_1.1',
            'role' => self::BUYER
        ],
        [
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'email' => self::SAME_ACCOUNT_USER_EMAIL,
            'password' => self::SAME_ACCOUNT_USER_PASSWORD,
            'enabled' => true,
            'confirmed' => true,
            'account' => 'account.level_1.1',
            'role' => self::BUYER
        ],
        [
            'first_name' => 'Jim',
            'last_name' => 'Smith',
            'email' => self::SUB_ACCOUNT_USER_EMAIL,
            'password' => self::SUB_ACCOUNT_USER_PASSWORD,
            'enabled' => true,
            'confirmed' => true,
            'account' => 'account.level_1.1.1',
            'role' => self::BUYER
        ],
        [
            'first_name' => 'Jack',
            'last_name' => 'Brown',
            'email' => self::NOT_SAME_ACCOUNT_USER_EMAIL,
            'password' => self::NOT_SAME_ACCOUNT_USER_PASSWORD,
            'enabled' => true,
            'confirmed' => true,
            'account' => 'account.level_1.2',
            'role' => self::BUYER
        ]
    ];

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /* @var $userManager BaseUserManager */
        $userManager = $this->container->get('orob2b_account_user.manager');
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();
        /* @var $accountUserRoleRepository AccountUserRoleRepository */
        $accountUserRoleRepository =  $this->container
            ->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:AccountUserRole')
            ->getRepository('OroB2BAccountBundle:AccountUserRole');

        foreach ($this->users as $user) {
            if ($userManager->findUserByUsernameOrEmail($user['email'])) {
                continue;
            }

            /* @var $entity AccountUser  */
            $entity = $userManager->createUser();

            /** @var AccountUserRole $role */
            $role = $accountUserRoleRepository->findOneBy(['role' => $user['role']]);

            /** @var Account $account */
            $account = $this->getReference($user['account']);

            $entity
                ->setFirstName($user['first_name'])
                ->setLastName($user['last_name'])
                ->setEmail($user['email'])
                ->setAccount($account)
                ->setConfirmed($user['confirmed'])
                ->setEnabled($user['enabled'])
                ->setSalt('')
                ->setPlainPassword($user['password'])
                ->setOrganization($organization)
                ->addOrganization($organization)
                ->addRole($role)
            ;

            $this->setReference($entity->getEmail(), $entity);

            $userManager->updateUser($entity);
        }
    }
}
