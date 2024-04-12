<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Owner\Metadata\ChainOwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\Tests\Functional\DataFixtures\SetRolePermissionsTrait;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadUserData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;
    use SetRolePermissionsTrait;

    public const USER1 = 'sale-user1';
    public const USER2 = 'sale-user2';

    public const ROLE1 = 'sale-role1';
    public const ROLE2 = 'sale-role2';
    public const ROLE3 = 'sale-role3';
    public const ROLE4 = 'sale-role4';
    public const ROLE5 = 'sale-role5';
    public const ROLE6 = 'sale-role6';
    public const ROLE7 = 'sale-role7';

    public const PARENT_ACCOUNT = 'sale-parent-customer';
    public const ACCOUNT1 = 'sale-customer1';
    public const ACCOUNT2 = 'sale-customer2';

    public const ACCOUNT1_USER1 = 'sale-customer1-user1@example.com';
    public const ACCOUNT1_USER2 = 'sale-customer1-user2@example.com';
    public const ACCOUNT1_USER3 = 'sale-customer1-user3@example.com';
    public const ACCOUNT2_USER1= 'sale-customer2-user1@example.com';
    public const PARENT_ACCOUNT_USER1 = 'sale-parent-customer-user1@example.com';
    public const PARENT_ACCOUNT_USER2 = 'sale-parent-customer-user2@example.com';

    private array $roles = [
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
            [
                'class' => CustomerAddress::class,
                'acls'  => ['VIEW_DEEP'],
            ],
        ],
    ];

    private array $customers = [
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

    private array $customerUsers = [
        [
            'email'     => self::ACCOUNT1_USER1,
            'firstname' => 'User1FN',
            'lastname'  => 'User1LN',
            'password'  => self::ACCOUNT1_USER1,
            'customer'  => self::ACCOUNT1,
            'userRoles' => [
                self::ROLE1,
                self::ROLE4,
            ],
        ],
        [
            'email'     => self::ACCOUNT1_USER2,
            'firstname' => 'User2FN',
            'lastname'  => 'User2LN',
            'password'  => self::ACCOUNT1_USER2,
            'customer'  => self::ACCOUNT1,
            'userRoles' => [
                self::ROLE2,
                self::ROLE7,
            ],
        ],
        [
            'email'     => self::ACCOUNT1_USER3,
            'firstname' => 'User3FN',
            'lastname'  => 'User3LN',
            'password'  => self::ACCOUNT1_USER3,
            'customer'  => self::ACCOUNT1,
            'userRoles' => [
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
            'customer'  => self::ACCOUNT2,
            'userRoles' => [
                self::ROLE1,
            ],
        ],
        [
            'email'     => self::PARENT_ACCOUNT_USER1,
            'firstname' => 'ParentUser1FN',
            'lastname'  => 'ParentUser1LN',
            'password'  => self::PARENT_ACCOUNT_USER1,
            'customer'  => self::PARENT_ACCOUNT,
            'userRoles' => [
                self::ROLE6
            ],
        ],
        [
            'email'     => self::PARENT_ACCOUNT_USER2,
            'firstname' => 'ParentUser2FN',
            'lastname'  => 'ParentUser2LN',
            'password'  => self::PARENT_ACCOUNT_USER2,
            'customer'  => self::PARENT_ACCOUNT,
            'userRoles' => [
                self::ROLE2,
            ],
        ],
    ];

    private array $users = [
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
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadUser::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $this->loadUsers();
        $this->loadRoles($manager);
        $this->loadCustomers($manager);
        $this->loadCustomerUsers();
    }

    private function loadRoles(ObjectManager $manager): void
    {
        /* @var AclManager $aclManager */
        $aclManager = $this->container->get('oro_security.acl.manager');
        foreach ($this->roles as $key => $items) {
            $role = new CustomerUserRole(CustomerUserRole::PREFIX_ROLE . $key);
            $role->setLabel($key);
            $manager->persist($role);
            foreach ($items as $acls) {
                $oidDescriptor = isset($acls['class'])
                    ? ObjectIdentityHelper::encodeIdentityString(EntityAclExtension::NAME, $acls['class'])
                    : $acls['oid'];
                $this->setRolePermissions($aclManager, $role, $oidDescriptor, $acls['acls']);
            }
            $this->setReference($key, $role);
        }
        $manager->flush();
        $aclManager->flush();
    }

    private function loadCustomers(ObjectManager $manager): void
    {
        /** @var User $defaultUser */
        $defaultUser = $this->getReference(LoadUser::USER);
        foreach ($this->customers as $item) {
            $customer = new Customer();
            $customer->setName($item['name']);
            $customer->setOrganization($defaultUser->getOrganization());
            if (isset($item['parent'])) {
                $customer->setParent($this->getReference($item['parent']));
            }
            $manager->persist($customer);
            $this->addReference($item['name'], $customer);
        }
        $manager->flush();
    }

    private function loadCustomerUsers(): void
    {
        /* @var CustomerUserManager $customerUserManager */
        $customerUserManager = $this->container->get('oro_customer_user.manager');
        /** @var User $defaultUser */
        $defaultUser = $this->getReference(LoadUser::USER);
        foreach ($this->customerUsers as $item) {
            /* @var CustomerUser $customerUser */
            $customerUser = $customerUserManager->createUser();
            $customerUser->setEmail($item['email']);
            $customerUser->setCustomer($this->getReference($item['customer']));
            $customerUser->setFirstName($item['firstname']);
            $customerUser->setLastName($item['lastname']);
            $customerUser->setConfirmed(true);
            $customerUser->setOwner($this->getReference(self::USER1));
            $customerUser->setOrganization($defaultUser->getOrganization());
            $customerUser->setSalt('');
            $customerUser->setPlainPassword($item['password']);
            $customerUser->setEnabled(true);
            foreach ($item['userRoles'] as $role) {
                $customerUser->addUserRole($this->getReference($role));
            }
            $customerUserManager->updateUser($customerUser);
            $this->setReference($item['email'], $customerUser);
        }
    }

    private function loadUsers(): void
    {
        /* @var UserManager $userManager */
        $userManager = $this->container->get('oro_user.manager');
        /** @var User $defaultUser */
        $defaultUser = $this->getReference(LoadUser::USER);
        $roles = $defaultUser->getUserRoles();
        foreach ($this->users as $item) {
            /* @var User $user */
            $user = $userManager->createUser();
            $user
                ->setEmail($item['email'])
                ->setFirstName($item['firstname'])
                ->setLastName($item['lastname'])
                ->setBusinessUnits($defaultUser->getBusinessUnits())
                ->setOwner($defaultUser->getOwner())
                ->setOrganization($defaultUser->getOrganization())
                ->addOrganization($defaultUser->getOrganization())
                ->addUserRole($roles[0])
                ->setUsername($item['username'])
                ->setPlainPassword($item['password'])
                ->setEnabled(true);
            $userManager->updateUser($user);
            $this->setReference($user->getUserIdentifier(), $user);
        }
    }

    private function setRolePermissions(
        AclManager $aclManager,
        CustomerUserRole $role,
        string $oidDescriptor,
        array $permissions
    ): void {
        /* @var ChainOwnershipMetadataProvider $chainMetadataProvider */
        $chainMetadataProvider = $this->container->get('oro_security.owner.metadata_provider.chain');
        $chainMetadataProvider->startProviderEmulation(FrontendOwnershipMetadataProvider::ALIAS);
        $this->setPermissions($aclManager, $role, [$oidDescriptor => $permissions]);
        $chainMetadataProvider->stopProviderEmulation();
    }
}
