<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;

use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Owner\Metadata\ChainMetadataProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserManager;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;

class LoadUserData extends AbstractFixture implements FixtureInterface
{
    const USER1 = 'rfp-user1';
    const USER2 = 'rfp-user2';

    const ROLE1 = 'rfp-role1';
    const ROLE2 = 'rfp-role2';
    const ROLE3 = 'rfp-role3';

    const ACCOUNT1 = 'rfp-account1';
    const ACCOUNT2 = 'rfp-account2';

    const ACCOUNT1_USER1    = 'rfp-account1-user1@example.com';
    const ACCOUNT1_USER2    = 'rfp-account1-user2@example.com';
    const ACCOUNT1_USER3    = 'rfp-account1-user3@example.com';
    const ACCOUNT2_USER1    = 'rfp-account2-user1@example.com';

    /**
     * @var array
     */
    protected $roles = [
        self::ROLE1 => [
            [
                'class' => 'orob2b_rfp.entity.request.class',
                'acls'  => ['VIEW_BASIC', 'CREATE_BASIC'],
            ],
            [
                'class' => 'orob2b_account.entity.account_user.class',
                'acls'  => [],
            ],
        ],
        self::ROLE2 => [
            [
                'class' => 'orob2b_rfp.entity.request.class',
                'acls'  => ['VIEW_LOCAL'],
            ],
            [
                'class' => 'orob2b_account.entity.account_user.class',
                'acls'  => ['VIEW_LOCAL'],
            ],
        ],
        self::ROLE3 => [
            [
                'class' => 'orob2b_rfp.entity.request.class',
                'acls'  => ['VIEW_BASIC'],
            ],
            [
                'class' => 'orob2b_account.entity.account_user.class',
                'acls'  => ['VIEW_LOCAL'],
            ],
        ],
    ];

    /**
     * @var array
     */
    protected $accounts = [
        [
            'name' => self::ACCOUNT2,
        ],
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
            'role'      => self::ROLE1,
        ],
        [
            'email'     => self::ACCOUNT1_USER2,
            'firstname' => 'User2FN',
            'lastname'  => 'User2LN',
            'password'  => self::ACCOUNT1_USER2,
            'account'   => self::ACCOUNT1,
            'role'      => self::ROLE2,
        ],
        [
            'email'     => self::ACCOUNT1_USER3,
            'firstname' => 'User3FN',
            'lastname'  => 'User3LN',
            'password'  => self::ACCOUNT1_USER3,
            'account'   => self::ACCOUNT1,
            'role'      => self::ROLE3,
        ],
        [
            'email'     => self::ACCOUNT2_USER1,
            'firstname' => 'User21FN',
            'lastname'  => 'User21LN',
            'password'  => self::ACCOUNT2_USER1,
            'account'   => self::ACCOUNT2,
            'role'      => self::ROLE1,
        ],
    ];

    /**
     * @var array
     */
    protected $users = [
        [
            'email'     => 'rfp-user1@example.com',
            'username'  => self::USER1,
            'password'  => self::USER1,
            'firstname' => 'RFPUser1FN',
            'lastname'  => 'RFPUser1LN',
        ],
        [
            'email'     => 'rfp-user2@example.com',
            'username'  => self::USER2,
            'password'  => self::USER2,
            'firstname' => 'RFPUser2FN',
            'lastname'  => 'RFPUser2LN',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->loadUsers($manager);
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
            ;
            $manager->persist($account);

            $this->addReference($item['name'], $account);
        }

        $manager->flush();
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
                ->setFirstName($item['firstname'])
                ->setLastName($item['lastname'])
                ->setConfirmed(true)
                ->addOrganization($organization)
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
}
