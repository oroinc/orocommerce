<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Api;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;

class RestValidateCustomerUserRelatedOrderTest extends RestJsonApiTestCase
{
    use RolePermissionExtension;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass('OroUserBundle:User');

        $organization = $em->getRepository('OroOrganizationBundle:Organization')->find(self::AUTH_ORGANIZATION);

        /** @var User $user */
        $user = $em->getRepository('OroUserBundle:User')->findOneBy([
            'email' => LoadAdminUserData::DEFAULT_ADMIN_EMAIL
        ]);

        foreach ($user->getRoles() as $existingRole) {
            $user->removeRole($existingRole);
        }

        $newRole = $em->getRepository('OroUserBundle:Role')->findOneBy(['role' => 'ROLE_USER']);
        $user->addRole($newRole);

        $userManager = $this->getContainer()->get('oro_user.manager');
        $userManager->updateUser($user);

        $token = new UsernamePasswordOrganizationToken($user, $user->getUsername(), 'main', $organization);
        $this->client->getContainer()->get('security.token_storage')->setToken($token);

        $this->updateRolePermissions(
            'ROLE_USER',
            CustomerUser::class,
            [
                'VIEW' => AccessLevel::SYSTEM_LEVEL,
                'CREATE' => AccessLevel::SYSTEM_LEVEL,
                'DELETE' => AccessLevel::SYSTEM_LEVEL,
                'ASSIGN' => AccessLevel::SYSTEM_LEVEL,
                'EDIT' => AccessLevel::SYSTEM_LEVEL,
            ]
        );

        $this->updateRolePermissions(
            'ROLE_USER',
            Customer::class,
            [
                'VIEW' => AccessLevel::SYSTEM_LEVEL,
                'CREATE' => AccessLevel::SYSTEM_LEVEL,
                'DELETE' => AccessLevel::SYSTEM_LEVEL,
                'ASSIGN' => AccessLevel::SYSTEM_LEVEL,
                'EDIT' => AccessLevel::SYSTEM_LEVEL,
            ]
        );

        $this->updateRolePermissions(
            'ROLE_USER',
            Order::class,
            [
                'VIEW' => AccessLevel::SYSTEM_LEVEL,
                'EDIT' => AccessLevel::NONE_LEVEL,
            ]
        );

        $this->loadFixtures([LoadCustomerUserData::class]);
    }

    public function testPatchCustomerValidationWithRelatedEntities()
    {
        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);
        $customer = $this->getReference('customer.level_1.1');

        $order = new Order();
        $order->setCustomerUser($customerUser);
        $manager = $this->getManager();
        $manager->persist($order);
        $manager->flush($order);

        $data = [
            'data' => [
                'type' => 'customerusers',
                'id' => (string)$customerUser->getId(),
                'relationships' => [
                    'customer' => [
                        'data' => [
                            'type' => 'customers',
                            'id' => (string)$customer->getId(),
                        ],
                    ]
                ],
            ]
        ];

        $response = $this->patch(
            [
                'entity' => 'customerusers',
                'id' => $customerUser->getId(),
            ],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'customer related entities constraint',
                'detail' => 'Can\'t change customer because you don\'t have permissions for updating ' .
                    'the following related entities: Order'
            ],
            $response
        );
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager|EntityManager
     */
    protected function getManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }
}
