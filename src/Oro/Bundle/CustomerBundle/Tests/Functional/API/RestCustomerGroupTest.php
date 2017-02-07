<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\API;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class RestCustomerGroupTest extends AbstractRestTest
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(
            [
                LoadUserData::class,
            ]
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetCustomerGroups()
    {
        $group = $this->createCustomerGroup('test group');
        $customer1 = $this->createCustomer('customer1', $group);
        $customer2 = $this->createCustomer('customer2', $group);

        $response = $this->get('oro_rest_api_cget', ['entity' => $this->getEntityType(CustomerGroup::class)]);

        $expected = [
            'data' => [
                [
                    'type' => 'customergroups',
                    'id' => '1',
                    'attributes' => [
                        'name' => 'Non-Authenticated Visitors',
                    ],
                    'relationships' => [
                        'customers' => ['data' => []],
                    ],
                ],
                [
                    'type' => 'customergroups',
                    'id' => (string)$group->getId(),
                    'attributes' => [
                        'name' => 'test group',
                    ],
                    'relationships' => [
                        'customers' => [
                            'data' => [
                                [
                                    'type' => 'customers',
                                    'id' => (string)$customer1->getId(),
                                ],
                                [
                                    'type' => 'customers',
                                    'id' => (string)$customer2->getId(),
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->assertResponseContains($expected, $response);
        $this->deleteEntities([$group, $customer1, $customer2]);
    }

    public function testDeleteByFilterCustomerGroup()
    {
        $this->createCustomerGroup('group to delete');

        $uri = $this->getUrl('oro_rest_api_cget', ['entity' => $this->getEntityType(CustomerGroup::class)]);
        $response = $this->request('DELETE', $uri, ['filter' => ['name' => 'group to delete']]);

        $this->assertResponseStatusCodeEquals($response, Response::HTTP_NO_CONTENT);
        $this->assertNull($this->getManager()->getRepository(CustomerGroup::class)->findOneByName('group to delete'));
    }

    public function testCreateCustomerGroup()
    {
        $customer1 = $this->createCustomer('customer1');
        $customer2 = $this->createCustomer('customer2');

        $data = [
            'data' => [
                'type' => 'customergroups',
                'attributes' => [
                    'name' => 'new group',
                ],
                'relationships' => [
                    'customers' => [
                        'data' => [
                            [
                                'type' => 'customers',
                                'id' => (string)$customer1->getId(),
                            ],
                            [
                                'type' => 'customers',
                                'id' => (string)$customer2->getId(),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $uri = $this->getUrl('oro_rest_api_post', ['entity' => $this->getEntityType(CustomerGroup::class)]);
        $response = $this->request('POST', $uri, $data);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $group = $this->getManager()->getRepository(CustomerGroup::class)->findOneByName('new group');

        $this->assertCount(2, $group->getCustomers());
        $this->assertContainsById($customer1, $group->getCustomers());
        $this->assertContainsById($customer2, $group->getCustomers());

        $this->deleteEntities([$customer1, $customer2, $group]);
    }

    public function testGetCustomerGroup()
    {
        $group = $this->createCustomerGroup('test group');
        $customer1 = $this->createCustomer('customer1', $group);
        $customer2 = $this->createCustomer('customer2', $group);

        $response = $this->get(
            'oro_rest_api_get',
            [
                'entity' => $this->getEntityType(CustomerGroup::class),
                'id' => (string)$group->getId(),
            ]
        );

        $expected = [
            'data' => [
                'type' => 'customergroups',
                'id' => (string)$group->getId(),
                'attributes' => [
                    'name' => 'test group',
                ],
                'relationships' => [
                    'customers' => [
                        'data' => [
                            [
                                'type' => 'customers',
                                'id' => (string)$customer1->getId(),
                            ],
                            [
                                'type' => 'customers',
                                'id' => (string)$customer2->getId(),
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->assertResponseContains($expected, $response);
        $this->deleteEntities([$group, $customer1, $customer2]);
    }

    public function testUpdateCustomerGroup()
    {
        $group = $this->createCustomerGroup('group to update');
        $customer1 = $this->createCustomer('customer1', $group);
        $customer2 = $this->createCustomer('customer2');
        $data = [
            'data' => [
                'type' => 'customergroups',
                'id' => (string)$group->getId(),
                'attributes' => [
                    'name' => 'updated group',
                ],
                'relationships' => [
                    'customers' => [
                        'data' => [
                            [
                                'type' => 'customers',
                                'id' => (string)$customer2->getId(),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $uri = $this->getUrl(
            'oro_rest_api_patch',
            [
                'entity' => $this->getEntityType(CustomerGroup::class),
                'id' => $group->getId(),
            ]
        );
        $response = $this->request('PATCH', $uri, $data);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $group = $this->getGroup('updated group');
        $this->assertCount(1, $group->getCustomers());
        $this->assertContainsById($customer2, $group->getCustomers());

        $this->deleteEntities([$customer1, $customer2, $group]);
    }

    public function testGetCustomersSubresource()
    {
        $group = $this->createCustomerGroup('new group');
        $customer = $this->createCustomer('customer', $group);

        $uri = $this->getUrl(
            'oro_rest_api_get_subresource',
            [
                'entity' => $this->getEntityType(CustomerGroup::class),
                'id' => $group->getId(),
                'association' => 'customers',
            ]
        );
        $response = $this->request('GET', $uri);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $customers = json_decode($response->getContent(), true)['data'];

        $this->assertCount(1, $customers);
        $this->assertEquals(reset($customers)['id'], $customer->getId());
        $this->deleteEntities([$group, $customer]);
    }

    public function testGetCustomersRelationship()
    {
        $group = $this->createCustomerGroup('test group');
        $customer1 = $this->createCustomer('customer1', $group);
        $customer2 = $this->createCustomer('customer2', $group);

        $uri = $this->getUrl(
            'oro_rest_api_get_relationship',
            [
                'entity' => $this->getEntityType(CustomerGroup::class),
                'id' => $group->getId(),
                'association' => 'customers',
            ]
        );

        $response = $this->request('GET', $uri);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $expected = [
            'data' => [
                [
                    'type' => 'customers',
                    'id' => (string)$customer1->getId(),
                ],
                [
                    'type' => 'customers',
                    'id' => (string)$customer2->getId(),
                ],
            ],
        ];
        $this->assertEquals($expected, $content);
        $this->deleteEntities([$customer1, $customer2, $group]);
    }

    public function testAddChildrenRelationship()
    {
        $group = $this->createCustomerGroup('test group');
        $customer1 = $this->createCustomer('customer1', $group);
        $customer2 = $this->createCustomer('customer2');

        $uri = $this->getUrl(
            'oro_rest_api_post_relationship',
            [
                'entity' => $this->getEntityType(CustomerGroup::class),
                'id' => $group->getId(),
                'association' => 'customers',
            ]
        );
        $data = [
            'data' => [
                [
                    'type' => 'customers',
                    'id' => (string)$customer2->getId(),
                ],
            ],
        ];
        $response = $this->request('POST', $uri, $data);
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        $group = $this->getGroup('test group');
        $this->assertCount(2, $group->getCustomers());
        $this->assertContainsById($customer1, $group->getCustomers());
        $this->assertContainsById($customer2, $group->getCustomers());

        $this->deleteEntities([$customer1, $customer2, $group]);
    }

    public function testPatchChildrenRelationship()
    {
        $group = $this->createCustomerGroup('test group');
        $customer1 = $this->createCustomer('customer1', $group);
        $customer2 = $this->createCustomer('customer2');

        $uri = $this->getUrl(
            'oro_rest_api_patch_relationship',
            [
                'entity' => $this->getEntityType(CustomerGroup::class),
                'id' => (string)$group->getId(),
                'association' => 'customers',
            ]
        );
        $data = [
            'data' => [
                [
                    'type' => 'customers',
                    'id' => (string)$customer2->getId(),
                ],
            ],
        ];
        $response = $this->request('PATCH', $uri, $data);
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        $group = $this->getGroup('test group');
        $this->assertCount(1, $group->getCustomers());
        $this->assertContainsById($customer2, $group->getCustomers());

        $this->deleteEntities([$customer1, $customer2, $group]);
    }

    public function testDeleteChildrenRelationship()
    {
        $group = $this->createCustomerGroup('test group');
        $customer1 = $this->createCustomer('customer1', $group);
        $customer2 = $this->createCustomer('customer2', $group);

        $uri = $this->getUrl(
            'oro_rest_api_delete_relationship',
            [
                'entity' => $this->getEntityType(CustomerGroup::class),
                'id' => (string)$group->getId(),
                'association' => 'customers',
            ]
        );
        $data = [
            'data' => [
                [
                    'type' => 'customers',
                    'id' => (string)$customer1->getId(),
                ],
            ],
        ];
        $response = $this->request('DELETE', $uri, $data);
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        $group = $this->getGroup('test group');
        $this->assertCount(1, $group->getCustomers());
        $this->assertContainsById($customer2, $group->getCustomers());

        $this->deleteEntities([$customer1, $customer2, $group]);
    }
}
