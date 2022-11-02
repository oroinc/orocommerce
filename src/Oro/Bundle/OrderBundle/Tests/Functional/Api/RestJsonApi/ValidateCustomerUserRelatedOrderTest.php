<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;

class ValidateCustomerUserRelatedOrderTest extends RestJsonApiTestCase
{
    use RolePermissionExtension;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([LoadCustomerUserData::class]);

        /** @var User $user */
        $user = $this->getReference('user');
        foreach ($user->getUserRoles() as $existingRole) {
            $user->removeUserRole($existingRole);
        }
        /** @var Role $newRole */
        $newRole = $this->getEntityManager()->getRepository(Role::class)->findOneBy(['role' => 'ROLE_USER']);
        $user->addUserRole($newRole);

        self::getContainer()->get('oro_user.manager')->updateUser($user);

        $token = new UsernamePasswordOrganizationToken($user, $user->getUsername(), 'main', $user->getOrganization());
        self::getContainer()->get('security.token_storage')->setToken($token);

        $this->updateRolePermissions(
            'ROLE_USER',
            CustomerUser::class,
            [
                'VIEW'   => AccessLevel::SYSTEM_LEVEL,
                'CREATE' => AccessLevel::SYSTEM_LEVEL,
                'DELETE' => AccessLevel::SYSTEM_LEVEL,
                'ASSIGN' => AccessLevel::SYSTEM_LEVEL,
                'EDIT'   => AccessLevel::SYSTEM_LEVEL
            ]
        );
        $this->updateRolePermissions(
            'ROLE_USER',
            Customer::class,
            [
                'VIEW'   => AccessLevel::SYSTEM_LEVEL,
                'CREATE' => AccessLevel::SYSTEM_LEVEL,
                'DELETE' => AccessLevel::SYSTEM_LEVEL,
                'ASSIGN' => AccessLevel::SYSTEM_LEVEL,
                'EDIT'   => AccessLevel::SYSTEM_LEVEL
            ]
        );
        $this->updateRolePermissions(
            'ROLE_USER',
            Order::class,
            [
                'VIEW' => AccessLevel::SYSTEM_LEVEL,
                'EDIT' => AccessLevel::NONE_LEVEL
            ]
        );
    }

    public function testPatchCustomerValidationWithRelatedEntities()
    {
        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);
        $customer = $this->getReference(LoadCustomers::CUSTOMER_LEVEL_1_DOT_1);

        $order = new Order();
        $order->setCustomerUser($customerUser);
        $em = $this->getEntityManager();
        $em->persist($order);
        $em->flush($order);

        $data = [
            'data' => [
                'type'          => 'customerusers',
                'id'            => (string)$customerUser->getId(),
                'relationships' => [
                    'customer' => [
                        'data' => [
                            'type' => 'customers',
                            'id'   => (string)$customer->getId()
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'customerusers', 'id' => $customerUser->getId()],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'customer related entities constraint',
                'detail' => 'Can\'t change customer because you don\'t have permissions for updating'
                    . ' the following related entities: Order',
                'source' => ['pointer' => '/data/relationships/customer/data']
            ],
            $response
        );
    }
}
