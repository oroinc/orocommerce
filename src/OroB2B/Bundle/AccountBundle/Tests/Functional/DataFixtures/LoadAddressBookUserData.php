<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Entity\Repository\RoleRepository;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;

use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Owner\Metadata\ChainMetadataProvider;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserManager;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;

class LoadAddressBookUserData extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    const USER1 = 'user1';
    const USER2 = 'user2';
    const USER3 = 'user3';
    const USER4 = 'user4';
    const USER5 = 'user5';

    /** VIEW ACCOUNT ADDRESS */
    const ROLE1_V_AC_AD = 'role-V_ac_addr';
    /** VIEW ACCOUNT USER ADDRESS */
    const ROLE2_V_ACU_AD = 'role-V_acu_addr';
    /** VIEW ACCOUNT ADDRESS AND VIEW ACCOUNT USER ADDRESS */
    const ROLE3_V_AC_AD_V_ACU_AD = 'role-V_ac_addr-V-acu_addr';
    const ROLE4_NONE = 'role_none';
    /** VIEW/CREATE/EDIT/DELETE ACCOUNT ADDRESS and ACCOUNT USER ADDRESS */
    const ROLE5_VCED_AC_AD_VCED_AU_AD = 'role-VCED_AC_ADD_VCED_AU_AD';

    const ACCOUNT1 = 'account1';

    const ACCOUNT1_USER1    = 'account1-user1@example.com';
    const ACCOUNT1_USER2    = 'account1-user2@example.com';
    const ACCOUNT1_USER3    = 'account1-user3@example.com';
    const ACCOUNT1_USER4    = 'account1-user4@example.com';
    const ACCOUNT1_USER5    = 'account1-user5@example.com';

    /**
     * @var array
     */
    protected $roles = [
        self::ROLE1_V_AC_AD => [
            [
                'class' => 'orob2b_account.entity.account_user_address.class',
                'acls'  => ['VIEW_BASIC'],
            ],
            [
                'class' => 'orob2b_account.entity.account_user.class',
                'acls'  => ['VIEW_LOCAL'],
            ],
        ],
        self::ROLE2_V_ACU_AD => [
            [
                'class' => 'orob2b_account.entity.account_user_address.class',
                'acls'  => ['VIEW_BASIC'],
            ],
            [
                'class' => 'orob2b_account.entity.account_user.class',
                'acls'  => [],
            ],
        ],
        self::ROLE3_V_AC_AD_V_ACU_AD => [
            [
                'class' => 'orob2b_account.entity.account_user_address.class',
                'acls'  => ['VIEW_BASIC'],
            ],
            [
                'class' => 'orob2b_account.entity.account_address.class',
                'acls'  => ['VIEW_BASIC'],
            ],
            [
                'class' => 'orob2b_account.entity.account_user.class',
                'acls'  => [],
            ],
        ],
        self::ROLE4_NONE => [
            [
                'class' => 'orob2b_account.entity.account_user.class',
                'acls'  => [],
            ],
        ],
        self::ROLE5_VCED_AC_AD_VCED_AU_AD => [
            [
                'class' => 'orob2b_account.entity.account_user_address.class',
                'acls'  => ['VIEW_BASIC', 'EDIT_BASIC', 'CREATE_BASIC'],
            ],
//            [
//                'class' => 'orob2b_account.entity.account_address.class',
//                'acls'  => ['VIEW_BASIC'],
//            ],
            [
                'class' => 'orob2b_account.entity.account_user.class',
                'acls'  => [],
            ],
        ]
    ];

    /**
     * @var array
     */
    protected $accounts = [
        [
            'name' => self::ACCOUNT1,
        ],
    ];

    /**
     * @var array
     */
    protected $accountUsers = [
        [
            'email'     => self::ACCOUNT1_USER1,
            'firstname' => 'User1FN',
            'lastname'  => 'User1LN',
            'password'  => self::ACCOUNT1_USER1,
            'account'   => self::ACCOUNT1,
            'role'      => self::ROLE1_V_AC_AD,
        ],
        [
            'email'     => self::ACCOUNT1_USER2,
            'firstname' => 'User2FN',
            'lastname'  => 'User2LN',
            'password'  => self::ACCOUNT1_USER2,
            'account'   => self::ACCOUNT1,
            'role'      => self::ROLE2_V_ACU_AD,
        ],
        [
            'email'     => self::ACCOUNT1_USER3,
            'firstname' => 'User3FN',
            'lastname'  => 'User3LN',
            'password'  => self::ACCOUNT1_USER3,
            'account'   => self::ACCOUNT1,
            'role'      => self::ROLE3_V_AC_AD_V_ACU_AD,
        ],
        [
            'email'     => self::ACCOUNT1_USER4,
            'firstname' => 'User3FN',
            'lastname'  => 'User3LN',
            'password'  => self::ACCOUNT1_USER4,
            'account'   => self::ACCOUNT1,
            'role'      => self::ROLE4_NONE,
        ],
        [
            'email'     => self::ACCOUNT1_USER5,
            'firstname' => 'User4FN',
            'lastname'  => 'User4LN',
            'password'  => self::ACCOUNT1_USER5,
            'account'   => self::ACCOUNT1,
            'role'      => self::ROLE5_VCED_AC_AD_VCED_AU_AD,
        ],
    ];

    /**
     * @var array
     */
    protected $users = [
        [
            'email'     => 'user1@example.com',
            'username'  => self::USER1,
            'password'  => self::USER1,
            'firstname' => 'User1FN',
            'lastname'  => 'User1LN',
        ],
        [
            'email'     => 'user2@example.com',
            'username'  => self::USER2,
            'password'  => self::USER2,
            'firstname' => 'User2FN',
            'lastname'  => 'User2LN',
        ],
    ];


    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadAdminUserData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
//        $this->loadUsers($manager);
        $this->loadRoles($manager);
        $this->loadAccounts($manager);
        $this->loadAccountUsers($manager);
    }

    /**
     * @param ObjectManager $manager
     */
    protected function loadAccounts(ObjectManager $manager)
    {
        $defaultUser    = $this->getUser($manager);
        $organization   = $defaultUser->getOrganization();

        foreach ($this->accounts as $item) {
            $account = new Account();
            $account
                ->setName($item['name'])
                ->setOrganization($organization)
                ->setOwner($defaultUser);
            ;
            $manager->persist($account);

            $this->addReference($item['name'], $account);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     */
    protected function loadUsers(ObjectManager $manager)
    {
        /* @var $userManager UserManager */
        $userManager    = $this->container->get('oro_user.manager');

        $defaultUser    = $this->getUser($manager);

        $businessUnit   = $defaultUser->getOwner();
        $organization   = $defaultUser->getOrganization();

        foreach ($this->users as $item) {
            /* @var $user User */
            $user = $userManager->createUser();

            $user
                ->setEmail($item['email'])
                ->setFirstName($item['firstname'])
                ->setLastName($item['lastname'])
                ->setBusinessUnits($defaultUser->getBusinessUnits())
                ->setOwner($businessUnit)
                ->setOrganization($organization)
                ->setUsername($item['username'])
                ->setPlainPassword($item['password'])
                ->setEnabled(true)
            ;
            $userManager->updateUser($user);

            $this->setReference($user->getUsername(), $user);
        }
    }

    /**
     * @param ObjectManager $manager
     */
    protected function loadRoles(ObjectManager $manager)
    {
        /* @var $aclManager AclManager */
        $aclManager = $this->container->get('oro_security.acl.manager');

        foreach ($this->roles as $key => $items) {
            $role = new AccountUserRole(AccountUserRole::PREFIX_ROLE . $key);
            $role->setLabel($key);

            foreach ($items as $acls) {
                $className = $this->container->getParameter($acls['class']);

                $this->setRolePermissions($aclManager, $role, $className, $acls['acls']);
            }

            $manager->persist($role);

            $this->setReference($key, $role);
        }

        $manager->flush();
        $aclManager->flush();
    }

    /**
     * @param ObjectManager $manager
     */
    protected function loadAccountUsers(ObjectManager $manager)
    {
        /* @var $userManager AccountUserManager */
        $userManager = $this->container->get('orob2b_account_user.manager');

        $defaultUser    = $this->getUser($manager);
        $organization   = $defaultUser->getOrganization();

        foreach ($this->accountUsers as $item) {
            /* @var $accountUser AccountUser */
            $accountUser = $userManager->createUser();

            $accountUser
                ->setEmail($item['email'])
                ->setAccount($this->getReference($item['account']))
                ->setOwner($defaultUser)
                ->setFirstName($item['firstname'])
                ->setLastName($item['lastname'])
                ->setConfirmed(true)
                ->addOrganization($organization)
                ->setOrganization($organization)
                ->addRole($this->getReference($item['role']))
                ->setSalt('')
                ->setPlainPassword($item['password'])
                ->setEnabled(true)
            ;

            $userManager->updateUser($accountUser);

            $this->setReference($item['email'], $accountUser);
        }
    }

    /**
     * @param AclManager $aclManager
     * @param AccountUserRole $role
     * @param string $className
     * @param array $allowedAcls
     */
    protected function setRolePermissions(AclManager $aclManager, AccountUserRole $role, $className, array $allowedAcls)
    {
        /* @var $chainMetadataProvider ChainMetadataProvider */
        $chainMetadataProvider = $this->container->get('oro_security.owner.metadata_provider.chain');

        if ($aclManager->isAclEnabled()) {
            $sid = $aclManager->getSid($role);

            foreach ($aclManager->getAllExtensions() as $extension) {
                if ($extension instanceof EntityAclExtension) {
                    $chainMetadataProvider->startProviderEmulation(FrontendOwnershipMetadataProvider::ALIAS);
                    $oid = $aclManager->getOid('entity:' . $className);
                    $builder = $aclManager->getMaskBuilder($oid);
                    $mask = $builder->reset()->get();
                    foreach ($allowedAcls as $acl) {
                        $mask = $builder->add($acl)->get();
                    }
                    $aclManager->setPermission($sid, $oid, $mask);

                    $chainMetadataProvider->stopProviderEmulation();
                }
            }
        }
    }


    /**
     * @param ObjectManager $manager
     * @return User
     */
    protected function getUser(ObjectManager $manager)
    {
        /** @var Role $role */
        $role = $manager->getRepository('OroUserBundle:Role')
            ->findOneBy(['role' => LoadRolesData::ROLE_ADMINISTRATOR]);

        if (!$role) {
            throw new \RuntimeException(sprintf('%s role should exist.', LoadRolesData::ROLE_ADMINISTRATOR));
        }

        /** @var RoleRepository $roleRepository */
        $roleRepository = $manager->getRepository('OroUserBundle:Role');
        $user = $roleRepository->getFirstMatchedUser($role);

        if (!$user) {
            throw new \RuntimeException(
                sprintf('At least one user with role %s should exist.', LoadRolesData::ROLE_ADMINISTRATOR)
            );
        }

        return $user;
    }
}
