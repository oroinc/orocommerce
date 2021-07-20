<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Owner\Metadata\ChainOwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\Tests\Functional\DataFixtures\SetRolePermissionsTrait;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

class LoadUserData extends AbstractFixture
{
    use SetRolePermissionsTrait;

    const USER1 = 'shop-user1';
    const USER2 = 'shop-user2';

    const ROLE1 = 'shop-role1';
    const ROLE2 = 'shop-role2';

    const ACCOUNT1 = 'shop-customer1';
    const ACCOUNT2 = 'shop-customer2';

    const ACCOUNT1_USER1    = 'shop-customer1-user1@example.com';
    const ACCOUNT1_USER2    = 'shop-customer1-user2@example.com';
    const ACCOUNT2_USER1    = 'shop-customer2-user1@example.com';

    /** @var array */
    private $roles = [
        self::ROLE1 => [
            [
                'class' => ShoppingList::class,
                'acls'  => ['VIEW_BASIC'],
            ],
            [
                'class' => CustomerUser::class,
                'acls'  => [],
            ],
        ],
        self::ROLE2 => [
            [
                'class' => ShoppingList::class,
                'acls'  => ['VIEW_LOCAL'],
            ],
            [
                'class' => Request::class,
                'acls'  => ['VIEW_BASIC', 'CREATE_BASIC'],
            ],
        ],
    ];

    /** @var array */
    private $customers = [
        [
            'name' => self::ACCOUNT1,
        ],
        [
            'name' => self::ACCOUNT2,
        ],
    ];

    /** @var array */
    private $customerUsers = [
        [
            'email'     => self::ACCOUNT1_USER2,
            'firstname' => 'User2FN',
            'lastname'  => 'User2LN',
            'password'  => self::ACCOUNT1_USER2,
            'customer'   => self::ACCOUNT1,
            'role'      => self::ROLE2,
        ],
        [
            'email'     => self::ACCOUNT1_USER1,
            'firstname' => 'User1FN',
            'lastname'  => 'User1LN',
            'password'  => self::ACCOUNT1_USER1,
            'customer'   => self::ACCOUNT1,
            'role'      => self::ROLE1,
        ],
        [
            'email'     => self::ACCOUNT2_USER1,
            'firstname' => 'User1FN',
            'lastname'  => 'User1LN',
            'password'  => self::ACCOUNT2_USER1,
            'customer'   => self::ACCOUNT2,
            'role'      => self::ROLE1,
        ],
    ];

    /** @var array */
    private $users = [
        [
            'email'     => 'shop-user1@example.com',
            'username'  => self::USER1,
            'password'  => self::USER1,
            'firstname' => 'ShopUser1FN',
            'lastname'  => 'ShopUser1LN',
        ],
        [
            'email'     => 'shop-user2@example.com',
            'username'  => self::USER2,
            'password'  => self::USER2,
            'firstname' => 'ShopUser2FN',
            'lastname'  => 'ShopUser2LN',
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

        foreach ($this->roles as $key => $roles) {
            $role = new CustomerUserRole(CustomerUserRole::PREFIX_ROLE . $key);
            $role->setLabel($key);
            $manager->persist($role);

            foreach ($roles as $acls) {
                $className = $acls['class'];

                $this->setRolePermissions($aclManager, $role, $className, $acls['acls']);
            }

            $this->setReference($key, $role);
        }

        $manager->flush();
        $aclManager->flush();
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
                ->setFirstName($item['firstname'])
                ->setLastName($item['lastname'])
                ->setCustomer($this->getReference($item['customer']))
                ->setEmail($item['email'])
                ->setConfirmed(true)
                ->setOrganization($organization)
                ->addUserRole($this->getReference($item['role']))
                ->setSalt('')
                ->setPlainPassword($item['password'])
                ->setEnabled(true);

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
                ->setFirstName($item['firstname'])
                ->setLastName($item['lastname'])
                ->setEmail($item['email'])
                ->setBusinessUnits($defaultUser->getBusinessUnits())
                ->setOwner($businessUnit)
                ->setOrganization($organization)
                ->addUserRole($roles[0])
                ->setUsername($item['username'])
                ->setPlainPassword($item['password'])
                ->setEnabled(true);
            $userManager->updateUser($user);

            $this->setReference($user->getUsername(), $user);
        }
    }

    private function loadCustomers(ObjectManager $manager)
    {
        $defaultUser = $this->getUser($manager);
        $organization = $defaultUser->getOrganization();

        foreach ($this->customers as $item) {
            $customer = new Customer();
            $customer
                ->setName($item['name'])
                ->setOrganization($organization);
            $manager->persist($customer);

            $this->addReference($item['name'], $customer);
        }

        $manager->flush();
    }

    /**
     * @param AclManager $aclManager
     * @param CustomerUserRole $role
     * @param string $className
     * @param string[] $permissions
     */
    private function setRolePermissions(
        AclManager $aclManager,
        CustomerUserRole $role,
        $className,
        array $permissions
    ) {
        /* @var ChainOwnershipMetadataProvider $chainMetadataProvider */
        $chainMetadataProvider = $this->container->get('oro_security.owner.metadata_provider.chain');
        $chainMetadataProvider->startProviderEmulation(FrontendOwnershipMetadataProvider::ALIAS);

        $this->setPermissions(
            $aclManager,
            $role,
            ['entity:' . $className => $permissions]
        );

        $chainMetadataProvider->stopProviderEmulation();
    }
}
