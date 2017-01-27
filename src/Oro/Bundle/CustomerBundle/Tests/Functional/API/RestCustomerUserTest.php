<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\API;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 *
 * @dbIsolation
 */
class RestCustomerUserTest extends RestJsonApiTestCase
{
    use UserUtilityTrait;
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([LoadCustomerUserData::class]);
    }

    public function testGetCustomerUsers()
    {
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);

        $uri = $this->getUrl('oro_rest_api_cget', ['entity' => $this->getEntityType(CustomerUser::class)]);
        $response = $this->request('GET', $uri, []);
        $this->assertApiResponseStatusCodeEquals($response, Response::HTTP_OK, CustomerUser::class, 'get list');
        $content = json_decode($response->getContent(), true);

        $expected = $this->getExpectedData($customerUser);
        $this->assertCount(7, $content['data']);
        $actualCustomerUser = $content['data'][1];
        $this->assertEquals($expected, $actualCustomerUser);
    }

    public function testGetCustomerUser()
    {
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);
        $expected = $this->getExpectedData($customerUser);
        $uri = $this->getUrl(
            'oro_rest_api_get',
            [
                'entity' => $this->getEntityType(CustomerUser::class),
                'id' => $customerUser->getId()
            ]
        );
        $response = $this->request('GET', $uri, []);
        $this->assertApiResponseStatusCodeEquals($response, Response::HTTP_OK, CustomerUser::class, 'get list');
        $content = json_decode($response->getContent(), true);
        $this->assertEquals($expected, $content['data']);
    }

    public function testGetCustomerUserRelations()
    {
        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);
        $expectedData = $this->getExpectedData($customerUser);
        $relations = [
            'owner',
            'customer',
            'organization',
            'roles',
            'salesRepresentatives'
        ];

        foreach ($relations as $relation) {
            $uri = $this->getUrl(
                'oro_rest_api_get_relationship',
                [
                    'entity' => $this->getEntityType(CustomerUser::class),
                    'id' => $customerUser->getId(),
                    'association' => $relation
                ]
            );
            $response = $this->request('GET', $uri, []);
            $this->assertApiResponseStatusCodeEquals($response, Response::HTTP_OK, CustomerUser::class, 'get list');
            $content = json_decode($response->getContent(), true);
            $this->assertEquals($expectedData['relationships'][$relation], $content);
        }
    }

    public function testDeleteByFilterCustomerUser()
    {
        $userName = 'CustomerUserTest';
        $this->createCustomerUser($userName);

        $uri = $this->getUrl(
            'oro_rest_api_cget',
            ['entity' => $this->getEntityType(CustomerUser::class)]
        );
        $response = $this->request('DELETE', $uri, ['filter' => ['username' => 'CustomerUserTest']]);

        $this->assertResponseStatusCodeEquals($response, Response::HTTP_NO_CONTENT);
        $customerUser = $this->getManager()->getRepository(CustomerUser::class)->findOneBy(['username' => $userName]);
        $this->assertNull($customerUser);
    }

    public function testCreateCustomer()
    {
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);
        $customer = $customerUser->getCustomer();
        $owner = $customerUser->getOwner();
        $organization = $customerUser->getOrganization();
        $role = $this->getContainer()->get('doctrine')->getRepository(CustomerUserRole::class)->find(1);
        $data = [
            'data' => [
                'type' => $this->getEntityType(CustomerUser::class),
                'attributes' => [
                    'username' => 'test2341@test.com',
                    'password' => '123123123123',
                    'email' => 'test2341@test.com',
                    'firstName' => 'Customer user',
                    'lastName' => 'Customer user',
                ],
                'relationships' => [
                    'owner' => [
                        'data' => [
                            'type' => 'users',
                            'id' => (string)$owner->getId(),
                        ],
                    ],
                    'salesRepresentatives' => [
                        'data' => [
                            [
                                'type' => 'users',
                                'id' => (string)$owner->getId(),
                            ],
                        ],
                    ],
                    'customer' => [
                        'data' => [
                            'type' => 'customers',
                            'id' => (string)$customer->getId(),
                        ],
                    ],
                    'roles' => [
                        'data' => [
                            [
                                'type' => 'customeruserroles',
                                'id' => (string)$role->getId(),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $uri = $this->getUrl(
            'oro_rest_api_post',
            ['entity' => $this->getEntityType(CustomerUser::class)]
        );
        $response = $this->request('POST', $uri, $data);
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $customerUser2 = $this->getManager()
            ->getRepository(CustomerUser::class)
            ->findOneBy(['email' => 'test2341@test.com']);
        $this->assertSame($organization->getId(), $customer->getOrganization()->getId());
        $this->assertSame($customerUser2->getCustomer()->getId(), $customer->getId());
        $this->assertSame($owner->getId(), $customerUser2->getOwner()->getId());

        $this->getManager()->remove($customerUser2);
        $this->getManager()->flush();
    }

    public function testPatchCustomerUser()
    {
        $customerUser = $this->createCustomerUser('testuser');
        $newFirstName = 'new first name';
        $data = [
            'data' => [
                'type' => $this->getEntityType(CustomerUser::class),
                'id' => (string)$customerUser->getId(),
                'attributes' => [
                    'firstName' => $newFirstName,
                ],
            ]
        ];
        $uri = $this->getUrl(
            'oro_rest_api_patch',
            [
                'entity' => $this->getEntityType(CustomerUser::class),
                'id' => $customerUser->getId(),
            ]
        );
        $response = $this->request('PATCH', $uri, $data);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $customerUser = $this->getManager()->find(CustomerUser::class, $customerUser->getId());

        $this->assertEquals($newFirstName, $customerUser->getFirstName());
        $this->getManager()->remove($customerUser);
        $this->getManager()->flush();
    }

    public function testDeleteCustomerUser()
    {
        $customerUser = $this->createCustomerUser('testuser');
        $uri = $this->getUrl(
            'oro_rest_api_delete',
            [
                'entity' => $this->getEntityType(CustomerUser::class),
                'id' => $customerUser->getId(),
            ]
        );
        $response = $this->request('DELETE', $uri);
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $uri = $this->getUrl(
            'oro_rest_api_get',
            [
                'entity' => $this->getEntityType(CustomerUser::class),
                'id' => $customerUser->getId()
            ]
        );
        $response = $this->request('GET', $uri, []);
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testPatchCustomer()
    {
        $customerUser = $this->createCustomerUser('testuser');
        $customer = $this->getReference('customer.level_1.1');

        $data = [
            'data' => [
                'type' => $this->getEntityType(CustomerUser::class),
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
        $uri = $this->getUrl(
            'oro_rest_api_patch',
            [
                'entity' => $this->getEntityType(CustomerUser::class),
                'id' => $customerUser->getId(),
            ]
        );
        $response = $this->request('PATCH', $uri, $data);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $customerUser = $this->getManager()->find(CustomerUser::class, $customerUser->getId());

        $this->assertEquals($customer->getId(), $customerUser->getCustomer()->getId());
        $this->getManager()->remove($customerUser);
        $this->getManager()->flush();
    }

    public function testPatchRoles()
    {
        $customerUser = $this->createCustomerUser('testuser');
        $customerUserRole = $this->getManager()
            ->getRepository(CustomerUserRole::class)
            ->findOneBy(['role' => 'ROLE_FRONTEND_BUYER']);

        $data = [
            'data' => [
                [
                    'type' => 'customeruserroles',
                    'id' => (string)$customerUserRole->getId()
                ]
            ]
        ];
        $uri = $this->getUrl(
            'oro_rest_api_patch_relationship',
            [
                'entity' => $this->getEntityType(CustomerUser::class),
                'id' => $customerUser->getId(),
                'association' => 'roles'
            ]
        );
        $response = $this->request('PATCH', $uri, $data);
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $customerUser = $this->getManager()->find(CustomerUser::class, $customerUser->getId());
        $this->assertNotNull($customerUser->getRole('ROLE_FRONTEND_BUYER'));
        $this->getManager()->remove($customerUser);
        $this->getManager()->flush();
    }

    public function testPostRoles()
    {
        $customerUser = $this->createCustomerUser('testuser2');
        $customerUserRole = $this->getManager()
            ->getRepository(CustomerUserRole::class)
            ->findOneBy(['role' => 'ROLE_FRONTEND_BUYER']);

        $data = [
            'data' => [
                [
                    'type' => 'customeruserroles',
                    'id' => (string)$customerUserRole->getId()
                ]
            ]
        ];
        $uri = $this->getUrl(
            'oro_rest_api_post_relationship',
            [
                'entity' => $this->getEntityType(CustomerUser::class),
                'id' => $customerUser->getId(),
                'association' => 'roles'
            ]
        );
        $response = $this->request('POST', $uri, $data);
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $customerUser = $this->getManager()->find(CustomerUser::class, $customerUser->getId());
        $this->assertNotNull($customerUser->getRole('ROLE_FRONTEND_BUYER'));
        $this->getManager()->remove($customerUser);
        $this->getManager()->flush();
    }

    public function testDeleteRoles()
    {
        $customerUser = $this->createCustomerUser('testuser3');
        $roleAdmin = $this->getManager()
            ->getRepository(CustomerUserRole::class)
            ->findOneBy(['role' => 'ROLE_FRONTEND_ADMINISTRATOR']);
        $roleBuyer = $this->getManager()
            ->getRepository(CustomerUserRole::class)
            ->findOneBy(['role' => 'ROLE_FRONTEND_BUYER']);


        $data = [
            'data' => [
                [
                    'type' => 'customeruserroles',
                    'id' => (string)$roleAdmin->getId()
                ],
                [
                    'type' => 'customeruserroles',
                    'id' => (string)$roleBuyer->getId()
                ]
            ]
        ];
        $uri = $this->getUrl(
            'oro_rest_api_post_relationship',
            [
                'entity' => $this->getEntityType(CustomerUser::class),
                'id' => $customerUser->getId(),
                'association' => 'roles'
            ]
        );
        $this->request('POST', $uri, $data);

        $data = [
            'data' => [
                [
                    'type' => 'customeruserroles',
                    'id' => (string)$roleBuyer->getId()
                ]
            ]
        ];
        $uri = $this->getUrl(
            'oro_rest_api_delete_relationship',
            [
                'entity' => $this->getEntityType(CustomerUser::class),
                'id' => $customerUser->getId(),
                'association' => 'roles'
            ]
        );
        $response = $this->request('DELETE', $uri, $data);
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $customerUser = $this->getManager()->find(CustomerUser::class, $customerUser->getId());
        $this->assertNull($customerUser->getRole('ROLE_FRONTEND_BUYER'));
        $this->getManager()->remove($customerUser);
        $this->getManager()->flush();
    }

    /**
     * @param CustomerUser $customerUser
     * @return array
     */
    private function getExpectedData(CustomerUser $customerUser)
    {
        $customerUserRole = $this->getManager()
            ->getRepository(CustomerUserRole::class)
            ->findOneBy(['role' => 'ROLE_FRONTEND_ADMINISTRATOR']);
        $createdAt = $customerUser->getCreatedAt();
        $updatedAt = $customerUser->getUpdatedAt();
        $passwordRequestedAt = $customerUser->getPasswordRequestedAt();
        $passwordChangedAt = $customerUser->getPasswordChangedAt();
        $expected = [
            'type' => 'customerusers',
            'id' => (string)$customerUser->getId(),
            'attributes' => [
                "confirmed" => $customerUser->isConfirmed(),
                "email" => $customerUser->getEmail(),
                "namePrefix" => $customerUser->getNamePrefix(),
                "firstName" => $customerUser->getFirstName(),
                "middleName" => $customerUser->getMiddleName(),
                "lastName" => $customerUser->getLastName(),
                "nameSuffix" => $customerUser->getNameSuffix(),
                "birthday" => $customerUser->getBirthday(),
                "createdAt" => $createdAt ? $createdAt->format('Y-m-d\TH:i:s\Z') : null,
                "updatedAt" => $updatedAt ? $updatedAt->format('Y-m-d\TH:i:s\Z') : null,
                "username" => $customerUser->getUsername(),
                "lastLogin" => $customerUser->getLastLogin(),
                "loginCount" => (string)$customerUser->getLoginCount(),
                "enabled" => $customerUser->isEnabled(),
                "passwordRequestedAt" => $passwordRequestedAt ? $passwordRequestedAt->format('Y-m-d\TH:i:s\Z') : null,
                "passwordChangedAt" => $passwordChangedAt ? $passwordChangedAt->format('Y-m-d\TH:i:s\Z') : null,
            ],
            'relationships' => [
                "owner" => [
                    "data" => [
                        "type" => "users",
                        "id" => (string)$customerUser->getOwner()->getId()
                    ]
                ],
                "salesRepresentatives" => [
                    "data" => []
                ],
                "organization" => [
                    "data" => [
                        "type" => "organizations",
                        "id" => (string)$customerUser->getOrganization()->getId()
                    ]
                ],
                'customer' => [
                    'data' => [
                        'type' => 'customers',
                        'id' => (string)$customerUser->getCustomer()->getId()
                    ]
                ],
                'roles' => [
                    'data' => [
                        [
                            'type' => 'customeruserroles',
                            'id' => (string)$customerUserRole->getId()
                        ]
                    ]
                ]
            ]
        ];

        return $expected;
    }

    /**
     * @param $name
     * @return CustomerUser
     */
    protected function createCustomerUser($name)
    {
        $manager = $this->getManager();
        $owner = $this->getFirstUser($manager);
        $customerUser = new CustomerUser();
        $customerUser->setOwner($owner);
        $customerUser->setUsername($name);
        $customerUser->setEmail($name);
        $customerUser->setFirstName('name');
        $customerUser->setLastName('surname');
        $customerUser->setEmail($name.'@test.com');
        $customerUser->setPassword('test');
        $manager->persist($customerUser);
        $manager->flush($customerUser);

        return $customerUser;
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager|EntityManager
     */
    protected function getManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }
}
