<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\API;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\API\DataFixtures\LoadCustomerData;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 *
 * @dbIsolation
 */
class RestCustomerTest extends AbstractRestTest
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(
            [
                LoadCustomerData::class,
                LoadUserData::class,
            ]
        );
    }

    /**
     * @group commerce
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetCustomers()
    {
        $uri = $this->getUrl('oro_rest_api_cget', ['entity' => $this->getEntityType(Customer::class)]);
        $response = $this->request('GET', $uri, []);

        $this->assertApiResponseStatusCodeEquals($response, Response::HTTP_OK, Customer::class, 'get list');
        $content = json_decode($response->getContent(), true);
        $defaultCustomer = $this->getDefaultCustomer();
        /** @var Customer $customer1 */
        $customer1 = $this->getReference('customer.1');

        $owner = $defaultCustomer->getOwner();
        $organization = $defaultCustomer->getOrganization();
        $expected = [
            'data' => [
                [
                    'type' => 'customers',
                    'id' => (string)$defaultCustomer->getId(),
                    'attributes' => [
                        'name' => $defaultCustomer->getName(),
                    ],
                    'relationships' => [
                        'parent' => ['data' => null,],
                        'children' => [
                            'data' => [
                                [
                                    'type' => 'customers',
                                    'id' => (string)$customer1->getId(),
                                ],
                            ],
                        ],
                        'users' => [
                            'data' => [
                                [
                                    'type' => 'customerusers',
                                    'id' => $defaultCustomer->getUsers()->first()->getId(),
                                ],
                            ],
                        ],
                        'owner' => [
                            'data' => ['type' => 'users', 'id' => (string)$owner->getId()],
                        ],
                        'organization' => [
                            'data' => ['type' => 'organizations', 'id' => (string)$organization->getId()],
                        ],
                        'salesRepresentatives' => ['data' => []],
                        'internal_rating' => ['data' => null],
                        'group' => ['data' => null],
                    ],
                ],
                [
                    'type' => 'customers',
                    'id' => (string)$customer1->getId(),
                    'attributes' =>
                        [
                            'name' => 'customer.1',
                        ],
                    'relationships' => $this->getRelationships(
                        $defaultCustomer->getId(),
                        $owner->getId(),
                        $organization->getId()
                    ),
                ],
            ],
        ];
        $this->assertEquals($expected, $content);
    }

    public function testDeleteByFilterCustomer()
    {
        $this->createCustomer('customer to delete');
        $this->getManager()->clear();

        $uri = $this->getUrl(
            'oro_rest_api_cget',
            ['entity' => $this->getEntityType(Customer::class)]
        );
        $response = $this->request('DELETE', $uri, ['filter' => ['name' => 'customer to delete']]);

        $this->assertResponseStatusCodeEquals($response, Response::HTTP_NO_CONTENT);
        $this->assertNull($this->getManager()->getRepository(Customer::class)->findOneByName('customer to delete'));
    }

    public function testCreateCustomer()
    {
        $parentCustomer = $this->getDefaultCustomer();
        $owner = $parentCustomer->getOwner();
        $organization = $parentCustomer->getOrganization();
        $group = $this->getGroup(LoadGroups::GROUP1);
        $data = [
            'data' => [
                'type' => $this->getEntityType(Customer::class),
                'attributes' => ['name' => 'created customer'],
                'relationships' => $this->getRelationships(
                    $parentCustomer->getId(),
                    $owner->getId(),
                    $organization->getId()
                ),
            ],
        ];

        $uri = $this->getUrl(
            'oro_rest_api_post',
            ['entity' => $this->getEntityType(Customer::class)]
        );
        $response = $this->request('POST', $uri, $data);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $customer = $this->getManager()->getRepository(Customer::class)->findOneByName('created customer');
        $this->assertSame($organization->getId(), $customer->getOrganization()->getId());
        $this->assertSame($parentCustomer->getId(), $customer->getParent()->getId());
        $this->assertSame($owner->getId(), $customer->getOwner()->getId());
        $this->assertSame('internal_rating.1 of 5', $customer->getInternalRating()->getName());
        $this->assertSame($group->getId(), $customer->getGroup()->getId());

        $this->deleteEntities([$customer]);
    }

    /**
     * @group commerce
     */
    public function testGetCustomer()
    {
        /** @var Customer $customer1 */
        $customer = $this->getReference('customer.1');
        $uri = $this->getUrl(
            'oro_rest_api_get',
            [
                'entity' => $this->getEntityType(Customer::class),
                'id' => (string)$customer->getId(),
            ]
        );
        $response = $this->request('GET', $uri, []);

        $this->assertApiResponseStatusCodeEquals($response, Response::HTTP_OK, Customer::class, 'get');
        $content = json_decode($response->getContent(), true);

        $parent = $this->getDefaultCustomer();
        $owner = $parent->getOwner();
        $organization = $parent->getOrganization();
        $expected = [
            'data' => [
                'type' => 'customers',
                'id' => (string)$customer->getId(),
                'attributes' =>
                    [
                        'name' => 'customer.1',
                    ],
                'relationships' => $this->getRelationships($parent->getId(), $owner->getId(), $organization->getId()),
            ],
        ];
        $this->assertEquals($expected, $content);
    }

    public function testUpdateCustomer()
    {
        $customer = $this->createCustomer(
            'customer to update',
            $this->getGroup(LoadGroups::GROUP1),
            'internal_rating.1 of 5'
        );
        $parentCustomer = $this->getReference('customer.1');
        $data = [
            'data' => [
                'type' => $this->getEntityType(Customer::class),
                'id' => (string)$customer->getId(),
                'attributes' => ['name' => 'customer updated'],
                'relationships' => [
                    'parent' => [
                        'data' => [
                            'type' => 'customers',
                            'id' => (string)$parentCustomer->getId(),
                        ],
                    ],
                    'internal_rating' => [
                        'data' => [
                            'type' => 'accinternalratings',
                            'id' => 'internal_rating.2_of_5',
                        ],
                    ],
                    'group' => [
                        'data' => [
                            'type' => 'customergroups',
                            'id' => (string)$this->getGroup(LoadGroups::GROUP2)->getId(),
                        ],
                    ],
                ],
            ],
        ];

        $uri = $this->getUrl(
            'oro_rest_api_patch',
            [
                'entity' => $this->getEntityType(Customer::class),
                'id' => $customer->getId(),
            ]
        );
        $response = $this->request('PATCH', $uri, $data);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $customer = $this->getManager()->getRepository(Customer::class)->findOneByName('customer updated');
        $this->assertSame($parentCustomer->getId(), $customer->getParent()->getId());
        $this->assertSame('internal_rating.2 of 5', $customer->getInternalRating()->getName());
        $this->assertSame($this->getGroup(LoadGroups::GROUP2)->getId(), $customer->getGroup()->getId());

        $this->deleteEntities([$customer]);
    }

    public function testGetGroupSubresource()
    {
        /** @var Customer $customer */
        $customer = $this->getReference('customer.1');

        $uri = $this->getUrl(
            'oro_rest_api_get_subresource',
            [
                'entity' => $this->getEntityType(Customer::class),
                'id' => $customer->getId(),
                'association' => 'group',
            ]
        );
        $response = $this->request('GET', $uri, []);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $expected = [
            'data' => [
                'type' => 'customergroups',
                'id' => (string)$customer->getGroup()->getId(),
                'attributes' => [
                    'name' => $customer->getGroup()->getName(),
                ],
                'relationships' => [
                    'customers' => [
                        'data' => [
                            ['type' => 'customers', 'id' => (string)$customer->getId()],
                        ],
                    ],
                ],
            ],
        ];
        $this->assertSame($expected, $content);
    }

    public function testGetGroupRelationship()
    {
        /** @var Customer $customer */
        $customer = $this->getReference('customer.1');

        $uri = $this->getUrl(
            'oro_rest_api_get_relationship',
            [
                'entity' => $this->getEntityType(Customer::class),
                'id' => $customer->getId(),
                'association' => 'group',
            ]
        );
        $response = $this->request('GET', $uri, []);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $expected = [
            'data' => [
                'type' => 'customergroups',
                'id' => (string)$customer->getGroup()->getId(),
            ],
        ];
        $this->assertEquals($expected, $content);
    }

    public function testUpdateGroupRelationship()
    {
        $customer = $this->createCustomer('customer to update group', $this->getGroup(LoadGroups::GROUP1));

        $uri = $this->getUrl(
            'oro_rest_api_patch_relationship',
            [
                'entity' => $this->getEntityType(Customer::class),
                'id' => $customer->getId(),
                'association' => 'group',
            ]
        );
        $data = [
            'data' => [
                'type' => 'customergroups',
                'id' => (string)$this->getGroup(LoadGroups::GROUP2)->getId(),
            ],
        ];
        $response = $this->request('PATCH', $uri, $data);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $customer = $this->getManager()->getRepository(Customer::class)->findOneByName('customer to update group');
        $this->assertSame($this->getGroup(LoadGroups::GROUP2)->getId(), $customer->getGroup()->getId());

        $this->deleteEntities([$customer]);
    }

    public function testGetInternalRatingSubresource()
    {
        /** @var Customer $customer */
        $customer = $this->getReference('customer.1');

        $uri = $this->getUrl(
            'oro_rest_api_get_subresource',
            [
                'entity' => $this->getEntityType(Customer::class),
                'id' => $customer->getId(),
                'association' => 'internal_rating',
            ]
        );
        $response = $this->request('GET', $uri, []);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $expected = [
            'data' => [
                'type' => 'accinternalratings',
                'id' => 'internal_rating.1_of_5',
                'attributes' => [
                    'name' => 'internal_rating.1 of 5',
                    'priority' => 1,
                    'default' => false,
                ],
            ],
        ];
        $this->assertSame($expected, $content);
    }

    public function testGetRatingRelationship()
    {
        /** @var Customer $customer */
        $customer = $this->getReference('customer.1');

        $uri = $this->getUrl(
            'oro_rest_api_get_relationship',
            [
                'entity' => $this->getEntityType(Customer::class),
                'id' => $customer->getId(),
                'association' => 'internal_rating',
            ]
        );
        $response = $this->request('GET', $uri, []);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $expected = [
            'data' => [
                'type' => 'accinternalratings',
                'id' => 'internal_rating.1_of_5',
            ],
        ];
        $this->assertEquals($expected, $content);
    }

    public function testUpdateRatingRelationship()
    {
        $customer = $this->createCustomer('customer to update rating');

        $uri = $this->getUrl(
            'oro_rest_api_patch_relationship',
            [
                'entity' => $this->getEntityType(Customer::class),
                'id' => $customer->getId(),
                'association' => 'internal_rating',
            ]
        );
        $data = [
            'data' => [
                'type' => 'accinternalratings',
                'id' => 'internal_rating.2_of_5',
            ],
        ];
        $response = $this->request('PATCH', $uri, $data);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $customer = $this->getManager()->getRepository(Customer::class)->findOneByName('customer to update rating');
        $this->assertSame('internal_rating.2 of 5', $customer->getInternalRating()->getName());

        $this->deleteEntities([$customer]);
    }

    public function testGetOrganizationSubresource()
    {
        /** @var Customer $customer */
        $customer = $this->getReference('customer.1');
        $organization = $customer->getOrganization();

        $uri = $this->getUrl(
            'oro_rest_api_get_subresource',
            [
                'entity' => $this->getEntityType(Customer::class),
                'id' => $customer->getId(),
                'association' => 'organization',
            ]
        );
        $response = $this->request('GET', $uri, []);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertSame($content['data']['type'], 'organizations');
        $this->assertSame($content['data']['id'], (string)$organization->getId());
    }

    public function testGetOrganizationRelationship()
    {
        /** @var Customer $customer */
        $customer = $this->getReference('customer.1');
        $organization = $customer->getOrganization();

        $uri = $this->getUrl(
            'oro_rest_api_get_relationship',
            [
                'entity' => $this->getEntityType(Customer::class),
                'id' => $customer->getId(),
                'association' => 'organization',
            ]
        );
        $response = $this->request('GET', $uri, []);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $expected = [
            'data' => [
                'type' => 'organizations',
                'id' => (string)$organization->getId(),
            ],
        ];
        $this->assertEquals($expected, $content);
    }

    public function testUpdateOrganizationRelationship()
    {
        $customer = $this->createCustomer('customer to update organization');
        $organization = new Organization();
        $organization->setName('org name')
            ->setEnabled(true);
        $this->getManager()->persist($organization);
        $this->getManager()->flush();

        $uri = $this->getUrl(
            'oro_rest_api_patch_relationship',
            [
                'entity' => $this->getEntityType(Customer::class),
                'id' => $customer->getId(),
                'association' => 'organization',
            ]
        );
        $data = [
            'data' => [
                'type' => 'organizations',
                'id' => (string)$organization->getId(),
            ],
        ];
        $response = $this->request('PATCH', $uri, $data);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $customer = $this->getManager()->getRepository(Customer::class)
            ->findOneByName('customer to update organization');
        $this->assertSame($organization->getId(), $customer->getOrganization()->getId());

        $this->deleteEntities([$customer]);
    }

    public function testGetOwnerSubresource()
    {
        /** @var Customer $customer */
        $customer = $this->getReference('customer.1');
        $owner = $customer->getOwner();

        $uri = $this->getUrl(
            'oro_rest_api_get_subresource',
            [
                'entity' => $this->getEntityType(Customer::class),
                'id' => $customer->getId(),
                'association' => 'owner',
            ]
        );
        $response = $this->request('GET', $uri, []);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);

        $this->assertSame($content['data']['type'], 'users');
        $this->assertSame($content['data']['id'], (string)$owner->getId());
    }

    public function testGetOwnerRelationship()
    {
        /** @var Customer $customer */
        $customer = $this->getReference('customer.1');
        $owner = $customer->getOwner();

        $uri = $this->getUrl(
            'oro_rest_api_get_relationship',
            [
                'entity' => $this->getEntityType(Customer::class),
                'id' => $customer->getId(),
                'association' => 'owner',
            ]
        );
        $response = $this->request('GET', $uri, []);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $expected = [
            'data' => [
                'type' => 'users',
                'id' => (string)$owner->getId(),
            ],
        ];
        $this->assertEquals($expected, $content);
    }

    public function testUpdateOwnerRelationship()
    {
        $customer = $this->createCustomer('customer to update owner');
        /** @var User $user */
        $user = $this->getReference(LoadUserData::SIMPLE_USER);

        $uri = $this->getUrl(
            'oro_rest_api_patch_relationship',
            [
                'entity' => $this->getEntityType(Customer::class),
                'id' => $customer->getId(),
                'association' => 'owner',
            ]
        );
        $data = [
            'data' => [
                'type' => 'users',
                'id' => (string)$user->getId(),
            ],
        ];
        $response = $this->request('PATCH', $uri, $data);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $customer = $this->getManager()->getRepository(Customer::class)
            ->findOneByName('customer to update owner');
        $this->assertSame($user->getId(), $customer->getOwner()->getId());

        $this->deleteEntities([$customer]);
    }

    /**
     * @group commerce
     */
    public function testGetParentSubresource()
    {
        /** @var Customer $customer */
        $customer = $this->getReference('customer.1');
        $parent = $customer->getParent();
        $owner = $customer->getOwner();
        $organization = $customer->getOrganization();

        $uri = $this->getUrl(
            'oro_rest_api_get_subresource',
            [
                'entity' => $this->getEntityType(Customer::class),
                'id' => $customer->getId(),
                'association' => 'parent',
            ]
        );
        $response = $this->request('GET', $uri, []);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $expected = [
            'data' => [
                'type' => 'customers',
                'id' => (string)$parent->getId(),
                'attributes' => [
                    'name' => $parent->getName(),
                ],
                'relationships' => [
                    'parent' => ['data' => null,],
                    'children' => [
                        'data' => [
                            [
                                'type' => 'customers',
                                'id' => (string)$customer->getId(),
                            ],
                        ],
                    ],
                    'users' => [
                        'data' => [
                            [
                                'type' => 'customerusers',
                                'id' => '1',
                            ],
                        ],
                    ],
                    'owner' => [
                        'data' => ['type' => 'users', 'id' => (string)$owner->getId()],
                    ],
                    'organization' => [
                        'data' => ['type' => 'organizations', 'id' => (string)$organization->getId()],
                    ],
                    'salesRepresentatives' => ['data' => []],
                    'internal_rating' => ['data' => null],
                    'group' => ['data' => null],
                ],
            ],
        ];
        $this->assertEquals($expected, $content);
    }

    public function testGetParentRelationship()
    {
        /** @var Customer $customer */
        $customer = $this->getReference('customer.1');
        $parent = $customer->getParent();

        $uri = $this->getUrl(
            'oro_rest_api_get_relationship',
            [
                'entity' => $this->getEntityType(Customer::class),
                'id' => $customer->getId(),
                'association' => 'parent',
            ]
        );
        $response = $this->request('GET', $uri, []);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $expected = [
            'data' => [
                'type' => 'customers',
                'id' => (string)$parent->getId(),
            ],
        ];
        $this->assertEquals($expected, $content);
    }

    public function testUpdateParentRelationship()
    {
        $customer = $this->createCustomer('customer to update parent');
        $parent = $this->getReference('customer.1');

        $uri = $this->getUrl(
            'oro_rest_api_patch_relationship',
            [
                'entity' => $this->getEntityType(Customer::class),
                'id' => $customer->getId(),
                'association' => 'parent',
            ]
        );
        $data = [
            'data' => [
                'type' => 'customers',
                'id' => (string)$parent->getId(),
            ],
        ];
        $response = $this->request('PATCH', $uri, $data);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $customer = $this->getManager()->getRepository(Customer::class)
            ->findOneByName('customer to update parent');
        $this->assertSame($parent->getId(), $customer->getParent()->getId());

        $this->deleteEntities([$customer]);
    }

    /**
     * @group commerce
     */
    public function testGetChildrenSubresource()
    {
        /** @var Customer $customer */
        $customer = $this->getDefaultCustomer();

        $uri = $this->getUrl(
            'oro_rest_api_get_subresource',
            [
                'entity' => $this->getEntityType(Customer::class),
                'id' => $customer->getId(),
                'association' => 'children',
            ]
        );
        $response = $this->request('GET', $uri, []);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);

        $owner = $customer->getOwner();
        $organization = $customer->getOrganization();
        $expected = [
            'data' => [
                [
                    'type' => 'customers',
                    'id' => (string)$this->getReference('customer.1')->getId(),
                    'attributes' =>
                        [
                            'name' => 'customer.1',
                        ],
                    'relationships' => $this->getRelationships(
                        $customer->getId(),
                        $owner->getId(),
                        $organization->getId()
                    ),
                ],
            ],
        ];
        $this->assertEquals($expected, $content);
    }

    public function testGetChildrenRelationship()
    {
        $customer = $this->getDefaultCustomer();

        $uri = $this->getUrl(
            'oro_rest_api_get_relationship',
            [
                'entity' => $this->getEntityType(Customer::class),
                'id' => $customer->getId(),
                'association' => 'children',
            ]
        );
        $response = $this->request('GET', $uri, []);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $expected = [
            'data' => [
                [
                    'type' => 'customers',
                    'id' => $customer->getChildren()->first()->getId(),
                ],
            ],
        ];
        $this->assertEquals($expected, $content);
    }

    public function testAddChildrenRelationship()
    {
        $customer = $this->createCustomer('new customer');
        $child = $this->createCustomer('child customer');
        $customer->addChild($child);
        $this->getManager()->flush();

        $additionalChild = $this->createCustomer('additional customer');

        $uri = $this->getUrl(
            'oro_rest_api_post_relationship',
            [
                'entity' => $this->getEntityType(Customer::class),
                'id' => $customer->getId(),
                'association' => 'children',
            ]
        );
        $data = [
            'data' => [
                [
                    'type' => 'customers',
                    'id' => (string)$additionalChild->getId(),
                ],
            ],
        ];
        $response = $this->request('POST', $uri, $data);
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        $customer = $this->getManager()->getRepository(Customer::class)->findOneByName('new customer');
        $this->assertCount(2, $customer->getChildren());
        $this->assertContainsById($additionalChild, $customer->getChildren());
        $this->assertContainsById($child, $customer->getChildren());
        $this->deleteEntities([$additionalChild, $child, $customer]);
    }

    public function testPatchChildrenRelationship()
    {
        $customer = $this->createCustomer('new customer');
        $child = $this->createCustomer('child customer');
        $customer->addChild($child);
        $this->getManager()->flush();

        $newChild = $this->createCustomer('new child customer');

        $uri = $this->getUrl(
            'oro_rest_api_patch_relationship',
            [
                'entity' => $this->getEntityType(Customer::class),
                'id' => $customer->getId(),
                'association' => 'children',
            ]
        );
        $data = [
            'data' => [
                [
                    'type' => 'customers',
                    'id' => (string)$newChild->getId(),
                ],
            ],
        ];
        $response = $this->request('PATCH', $uri, $data);
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        $customer = $this->getManager()->getRepository(Customer::class)->findOneByName('new customer');
        $this->assertCount(1, $customer->getChildren());
        $this->assertContainsById($newChild, $customer->getChildren());

        $this->deleteEntities([$child, $newChild, $customer]);
    }

    public function testDeleteChildrenRelationship()
    {
        $customer = $this->createCustomer('new customer');
        $child1 = $this->createCustomer('child 1');
        $child2 = $this->createCustomer('child 2');
        $customer->addChild($child1);
        $customer->addChild($child2);

        $this->getManager()->flush();
        $uri = $this->getUrl(
            'oro_rest_api_delete_relationship',
            [
                'entity' => $this->getEntityType(Customer::class),
                'id' => $customer->getId(),
                'association' => 'children',
            ]
        );
        $data = [
            'data' => [
                [
                    'type' => 'customers',
                    'id' => (string)$child1->getId(),
                ],
            ],
        ];
        $response = $this->request('DELETE', $uri, $data);
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        $customer = $this->getManager()->getRepository(Customer::class)->findOneByName('new customer');
        $this->assertCount(1, $customer->getChildren());
        $this->assertContainsById($child2, $customer->getChildren());

        $this->deleteEntities([$customer, $child1, $child2]);
    }

    public function testGetUsersSubresource()
    {
        /** @var Customer $customer */
        $customer = $this->getDefaultCustomer();
        /** @var CustomerUser $user */
        $user = $customer->getUsers()->first();

        $uri = $this->getUrl(
            'oro_rest_api_get_subresource',
            [
                'entity' => $this->getEntityType(Customer::class),
                'id' => $customer->getId(),
                'association' => 'users',
            ]
        );
        $response = $this->request('GET', $uri, []);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertCount(1, $content['data']);
        $this->assertSame('customerusers', reset($content['data'])['type']);
        $this->assertSame((string)$user->getId(), reset($content['data'])['id']);
    }

    public function testGetUsersRelationship()
    {
        $customer = $this->getDefaultCustomer();

        $uri = $this->getUrl(
            'oro_rest_api_get_relationship',
            [
                'entity' => $this->getEntityType(Customer::class),
                'id' => $customer->getId(),
                'association' => 'users',
            ]
        );
        $response = $this->request('GET', $uri, []);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $expected = [
            'data' => [
                [
                    'type' => 'customerusers',
                    'id' => $customer->getUsers()->first()->getId(),
                ],
            ],
        ];
        $this->assertEquals($expected, $content);
    }

    public function testAddUsersRelationship()
    {
        $customer = $this->createCustomer('new customer');
        $user1 = $this->createCustomerUser('user1@oroinc.com', $customer);
        $user2 = $this->createCustomerUser('user2@oroinc.com');

        $uri = $this->getUrl(
            'oro_rest_api_post_relationship',
            [
                'entity' => $this->getEntityType(Customer::class),
                'id' => $customer->getId(),
                'association' => 'users',
            ]
        );
        $data = [
            'data' => [
                [
                    'type' => 'customerusers',
                    'id' => (string)$user2->getId(),
                ],
            ],
        ];
        $response = $this->request('POST', $uri, $data);
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        $customer = $this->getManager()->getRepository(Customer::class)->findOneByName('new customer');
        $this->assertCount(2, $customer->getUsers());
        $this->assertContainsById($user1, $customer->getUsers());
        $this->assertContainsById($user2, $customer->getUsers());

        $this->deleteEntities([$user1, $user2, $customer]);
    }

    public function testPatchUsersRelationship()
    {
        $customer = $this->createCustomer('new customer');
        $user1 = $this->createCustomerUser('user1@oroinc.com', $customer);
        $user2 = $this->createCustomerUser('user2@oroinc.com');

        $uri = $this->getUrl(
            'oro_rest_api_patch_relationship',
            [
                'entity' => $this->getEntityType(Customer::class),
                'id' => $customer->getId(),
                'association' => 'users',
            ]
        );
        $data = [
            'data' => [
                [
                    'type' => 'customerusers',
                    'id' => (string)$user2->getId(),
                ],
            ],
        ];
        $response = $this->request('PATCH', $uri, $data);
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        $customer = $this->getManager()->getRepository(Customer::class)->findOneByName('new customer');
        $this->assertCount(1, $customer->getUsers());
        $this->assertContainsById($user2, $customer->getUsers());

        $this->deleteEntities([$user1, $user2, $customer]);
    }

    public function testDeleteUsersRelationship()
    {
        $customer = $this->createCustomer('new customer');
        $user1 = $this->createCustomerUser('user1@oroinc.com', $customer);
        $user2 = $this->createCustomerUser('user2@oroinc.com', $customer);

        $uri = $this->getUrl(
            'oro_rest_api_delete_relationship',
            [
                'entity' => $this->getEntityType(Customer::class),
                'id' => $customer->getId(),
                'association' => 'users',
            ]
        );
        $data = [
            'data' => [
                [
                    'type' => 'customerusers',
                    'id' => (string)$user1->getId(),
                ],
            ],
        ];
        $response = $this->request('DELETE', $uri, $data);
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        $customer = $this->getManager()->getRepository(Customer::class)->findOneByName('new customer');
        $this->assertCount(1, $customer->getUsers());
        $this->assertContainsById($user2, $customer->getUsers());

        $this->deleteEntities([$user1, $user2, $customer]);
    }

    /**
     * @param int $parentId
     * @param int $ownerId
     * @param int $organizationId
     * @return array
     */
    protected function getRelationships($parentId, $ownerId, $organizationId)
    {
        return [
            'parent' => [
                'data' => [
                    'type' => 'customers',
                    'id' => (string)$parentId,
                ],
            ],
            'children' => ['data' => [],],
            'users' => ['data' => [],],
            'owner' => [
                'data' => [
                    'type' => 'users',
                    'id' => (string)$ownerId,
                ],
            ],
            'organization' => [
                'data' => [
                    'type' => 'organizations',
                    'id' => (string)$organizationId,
                ],
            ],
            'salesRepresentatives' => [
                'data' => [
                    [
                        'type' => 'users',
                        'id' => (string)$ownerId,
                    ],
                ],
            ],
            'internal_rating' => [
                'data' =>
                    [
                        'type' => 'accinternalratings',
                        'id' => 'internal_rating.1_of_5',
                    ],
            ],
            'group' => [
                'data' => [
                    'type' => 'customergroups',
                    'id' => (string)$this->getGroup(LoadGroups::GROUP1)->getId(),
                ],
            ],
        ];
    }
}
