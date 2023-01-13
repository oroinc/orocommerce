<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerUserRoleRepository;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadCustomerUsersData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    const ADMINISTRATOR = 'ROLE_FRONTEND_ADMINISTRATOR';
    const BUYER         = 'ROLE_FRONTEND_BUYER';

    const USER_NAME      = 'John';
    const USER_LAST_NAME = 'Doe';
    const USER_EMAIL     = 'user@example.com';
    const USER_PASSWORD  = '123123';

    const SUB_ACCOUNT_USER_EMAIL = 'sub_customer@example.com';
    const SUB_ACCOUNT_USER_PASSWORD ='147147';

    const SAME_ACCOUNT_USER_EMAIL    = 'same_customer@example.com';
    const SAME_ACCOUNT_USER_PASSWORD = '456456';

    const NOT_SAME_ACCOUNT_USER_EMAIL    = 'not_same_customer@example.com';
    const NOT_SAME_ACCOUNT_USER_PASSWORD = '789789';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCustomers::class
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
            'customer' => 'customer.level_1.1',
            'role' => self::BUYER
        ],
        [
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'email' => self::SAME_ACCOUNT_USER_EMAIL,
            'password' => self::SAME_ACCOUNT_USER_PASSWORD,
            'enabled' => true,
            'confirmed' => true,
            'customer' => 'customer.level_1.1',
            'role' => self::BUYER
        ],
        [
            'first_name' => 'Jim',
            'last_name' => 'Smith',
            'email' => self::SUB_ACCOUNT_USER_EMAIL,
            'password' => self::SUB_ACCOUNT_USER_PASSWORD,
            'enabled' => true,
            'confirmed' => true,
            'customer' => 'customer.level_1.1.1',
            'role' => self::BUYER
        ],
        [
            'first_name' => 'Jack',
            'last_name' => 'Brown',
            'email' => self::NOT_SAME_ACCOUNT_USER_EMAIL,
            'password' => self::NOT_SAME_ACCOUNT_USER_PASSWORD,
            'enabled' => true,
            'confirmed' => true,
            'customer' => 'customer.level_1.2',
            'role' => self::BUYER
        ]
    ];

    public function load(ObjectManager $manager)
    {
        /* @var BaseUserManager $userManager */
        $userManager = $this->container->get('oro_customer_user.manager');
        $organization = $manager->getRepository(Organization::class)->getFirst();
        /* @var CustomerUserRoleRepository $customerUserRoleRepository */
        $customerUserRoleRepository =  $this->container->get('doctrine')->getRepository(CustomerUserRole::class);

        foreach ($this->users as $user) {
            if ($userManager->findUserByUsernameOrEmail($user['email'])) {
                continue;
            }

            /* @var CustomerUser $entity */
            $entity = $userManager->createUser();

            /** @var CustomerUserRole $role */
            $role = $customerUserRoleRepository->findOneBy(['role' => $user['role']]);

            /** @var Customer $customer */
            $customer = $this->getReference($user['customer']);

            $entity
                ->setFirstName($user['first_name'])
                ->setLastName($user['last_name'])
                ->setEmail($user['email'])
                ->setCustomer($customer)
                ->setOwner($customer->getOwner())
                ->setConfirmed($user['confirmed'])
                ->setEnabled($user['enabled'])
                ->setSalt('')
                ->setPlainPassword($user['password'])
                ->setOrganization($organization)
                ->addUserRole($role)
            ;

            $this->setReference($entity->getEmail(), $entity);

            $userManager->updateUser($entity);
        }
    }
}
