<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Owner\Metadata\ChainOwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\Tests\Functional\DataFixtures\SetRolePermissionsTrait;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

class LoadUserData extends AbstractFixture
{
    use SetRolePermissionsTrait;

    const USER1 = 'sale-user1';
    const USER2 = 'sale-user2';

    const ROLE1 = 'sale-role1';
    const ROLE2 = 'sale-role2';
    const ROLE3 = 'sale-role3';
    const ROLE4 = 'sale-role4';
    const ROLE5 = 'sale-role5';
    const ROLE6 = 'sale-role6';
    const ROLE7 = 'sale-role7';

    const PARENT_ACCOUNT = 'sale-parent-customer';
    const ACCOUNT1 = 'sale-customer1';
    const ACCOUNT2 = 'sale-customer2';

    const ACCOUNT1_USER1    = 'sale-customer1-user1@example.com';
    const ACCOUNT1_USER2    = 'sale-customer1-user2@example.com';
    const ACCOUNT1_USER3    = 'sale-customer1-user3@example.com';
    const ACCOUNT2_USER1    = 'sale-customer2-user1@example.com';
    const PARENT_ACCOUNT_USER1    = 'sale-parent-customer-user1@example.com';
    const PARENT_ACCOUNT_USER2    = 'sale-parent-customer-user2@example.com';

    /** @var array */
    private $roles = [
        self::ROLE1 => [
            [
                'class' => Quote::class,
                'acls'  => ['VIEW_BASIC'],
            ],
            [
                'class' => CustomerUser::class,
                'acls'  => [],
            ],
        ],
        self::ROLE2 => [
            [
                'class' => Quote::class,
                'acls'  => ['VIEW_LOCAL'],
            ],
            [
                'class' => CustomerUser::class,
                'acls'  => [],
            ],
        ],
        self::ROLE3 => [
            [
                'class' => Quote::class,
                'acls'  => ['VIEW_LOCAL'],
            ],
            [
                'class' => CustomerUser::class,
                'acls'  => ['VIEW_LOCAL'],
            ]
        ],
        self::ROLE4 => [
            [
                'class' => Order::class,
                'acls'  => [],
            ],
        ],
        self::ROLE5 => [
            [
                'class' => Order::class,
                'acls'  => ['VIEW_BASIC', 'CREATE_BASIC'],
            ],
        ],
        self::ROLE6 => [
            [
                'class' => Quote::class,
                'acls'  => ['VIEW_DEEP'],
            ],
            [
                'class' => CustomerUser::class,
                'acls'  => ['VIEW_DEEP'],
            ],
            [
                'class' => Checkout::class,
                'acls'  => ['VIEW_DEEP', 'CREATE_LOCAL'],
            ],
        ],
        self::ROLE7 => [
            [
                'class' => Checkout::class,
                'acls'  => ['VIEW_LOCAL', 'EDIT_LOCAL', 'CREATE_LOCAL'],
            ],
            [
                'oid'  => 'workflow:(root)',
                'acls' => ['VIEW_WORKFLOW_SYSTEM', 'PERFORM_TRANSITIONS_SYSTEM'],
            ],
        ],
    ];

    /** @var array */
    private $customers = [
        [
            'name' => self::PARENT_ACCOUNT,
        ],
        [
            'name' => self::ACCOUNT1,
            'parent' => self::PARENT_ACCOUNT
        ],
        [
            'name' => self::ACCOUNT2,
            'parent' => self::PARENT_ACCOUNT
        ],
    ];

    /** @var array */
    private $customerUsers = [
        [
            'email'     => self::ACCOUNT1_USER1,
            'firstname' => 'User1FN',
            'lastname'  => 'User1LN',
            'password'  => self::ACCOUNT1_USER1,
            'customer'   => self::ACCOUNT1,
            'userRoles'     => [
                self::ROLE1,
                self::ROLE4,
            ],
        ],
        [
            'email'     => self::ACCOUNT1_USER2,
            'firstname' => 'User2FN',
            'lastname'  => 'User2LN',
            'password'  => self::ACCOUNT1_USER2,
            'customer'   => self::ACCOUNT1,
            'userRoles'     => [
                self::ROLE2,
                self::ROLE7,
            ],
        ],
        [
            'email'     => self::ACCOUNT1_USER3,
            'firstname' => 'User3FN',
            'lastname'  => 'User3LN',
            'password'  => self::ACCOUNT1_USER3,
            'customer'   => self::ACCOUNT1,
            'userRoles'     => [
                self::ROLE3,
                self::ROLE5,
                self::ROLE7,
            ],
        ],
        [
            'email'     => self::ACCOUNT2_USER1,
            'firstname' => 'User1FN',
            'lastname'  => 'User1LN',
            'password'  => self::ACCOUNT2_USER1,
            'customer'   => self::ACCOUNT2,
            'userRoles'     => [
                self::ROLE1,
            ],
        ],
        [
            'email'     => self::PARENT_ACCOUNT_USER1,
            'firstname' => 'ParentUser1FN',
            'lastname'  => 'ParentUser1LN',
            'password'  => self::PARENT_ACCOUNT_USER1,
            'customer'   => self::PARENT_ACCOUNT,
            'userRoles'     => [
                self::ROLE6
            ],
        ],
        [
            'email'     => self::PARENT_ACCOUNT_USER2,
            'firstname' => 'ParentUser2FN',
            'lastname'  => 'ParentUser2LN',
            'password'  => self::PARENT_ACCOUNT_USER2,
            'customer'   => self::PARENT_ACCOUNT,
            'userRoles'     => [
                self::ROLE2,
            ],
        ],
    ];

    /** @var array */
    private $users = [
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
        $this->loadUsers($manager);
        $this->loadRoles($manager);
        $this->loadCustomers($manager);
        $this->loadCustomerUsers($manager);
    }

    private function loadRoles(ObjectManager $manager)
    {
        /* @var AclManager $aclManager */
        $aclManager = $this->container->get('oro_security.acl.manager');

        foreach ($this->roles as $key => $items) {
            $role = new CustomerUserRole(CustomerUserRole::PREFIX_ROLE . $key);
            $role->setLabel($key);
            $manager->persist($role);

            foreach ($items as $acls) {
                $oidDescriptor = isset($acls['class'])
                    ? 'entity:' . $acls['class']
                    : $acls['oid'];
                $this->setRolePermissions($aclManager, $role, $oidDescriptor, $acls['acls']);
            }

            $this->setReference($key, $role);
        }

        $manager->flush();
        $aclManager->flush();
    }

    private function loadCustomers(ObjectManager $manager)
    {
        $defaultUser    = $this->getUser($manager);
        $organization   = $defaultUser->getOrganization();

        foreach ($this->customers as $item) {
            $customer = new Customer();
            $customer
                ->setName($item['name'])
                ->setOrganization($organization)
            ;
            if (isset($item['parent'])) {
                $customer->setParent($this->getReference($item['parent']));
            }
            $manager->persist($customer);

            $this->addReference($item['name'], $customer);
        }

        $manager->flush();
    }

    private function loadCustomerUsers(ObjectManager $manager)
    {
        /* @var CustomerUserManager $userManager */
        $userManager = $this->container->get('oro_customer_user.manager');

        $defaultUser = $this->getUser($manager);
        $organization = $defaultUser->getOrganization();

        foreach ($this->customerUsers as $item) {
            /* @var CustomerUser $customerUser */
            $customerUser = $userManager->createUser();

            $customerUser
                ->setOwner($defaultUser)
                ->setEmail($item['email'])
                ->setCustomer($this->getReference($item['customer']))
                ->setFirstName($item['firstname'])
                ->setLastName($item['lastname'])
                ->setConfirmed(true)
                ->setOwner($this->getReference(self::USER1))
                ->setOrganization($organization)
                ->setSalt('')
                ->setPlainPassword($item['password'])
                ->setEnabled(true);

            foreach ($item['userRoles'] as $role) {
                $customerUser->addUserRole($this->getReference($role));
            }

            $userManager->updateUser($customerUser);

            $this->setReference($item['email'], $customerUser);
        }
    }

    private function loadUsers(ObjectManager $manager)
    {
        /* @var UserManager $userManager */
        $userManager = $this->container->get('oro_user.manager');

        $defaultUser = $this->getUser($manager);

        $businessUnit = $defaultUser->getOwner();
        $organization = $defaultUser->getOrganization();
        $roles = $defaultUser->getUserRoles();

        foreach ($this->users as $item) {
            /* @var User $user */
            $user = $userManager->createUser();
            $user
                ->setEmail($item['email'])
                ->setFirstName($item['firstname'])
                ->setLastName($item['lastname'])
                ->setBusinessUnits($defaultUser->getBusinessUnits())
                ->setOwner($businessUnit)
                ->setOrganization($organization)
                ->addOrganization($organization)
                ->addUserRole($roles[0])
                ->setUsername($item['username'])
                ->setPlainPassword($item['password'])
                ->setEnabled(true);
            $userManager->updateUser($user);

            $this->setReference($user->getUsername(), $user);
        }
    }

    /**
     * @param AclManager       $aclManager
     * @param CustomerUserRole $role
     * @param string           $oidDescriptor
     * @param string[]         $permissions
     */
    private function setRolePermissions(
        AclManager $aclManager,
        CustomerUserRole $role,
        string $oidDescriptor,
        array $permissions
    ) {
        /* @var ChainOwnershipMetadataProvider $chainMetadataProvider */
        $chainMetadataProvider = $this->container->get('oro_security.owner.metadata_provider.chain');
        $chainMetadataProvider->startProviderEmulation(FrontendOwnershipMetadataProvider::ALIAS);

        $this->setPermissions(
            $aclManager,
            $role,
            [$oidDescriptor => $permissions]
        );

        $chainMetadataProvider->stopProviderEmulation();
    }
}
