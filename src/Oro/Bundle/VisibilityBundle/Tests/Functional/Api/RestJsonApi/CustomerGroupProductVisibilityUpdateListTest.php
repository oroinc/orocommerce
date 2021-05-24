<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiUpdateListTestCase;
use Oro\Bundle\VisibilityBundle\Async\Topics;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;

/**
 * @group CommunityEdition
 *
 * @dbIsolationPerTest
 */
class CustomerGroupProductVisibilityUpdateListTest extends RestJsonApiUpdateListTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->getOptionalListenerManager()->enableListener('oro_visibility.entity_listener.product_visibility_change');
        $this->loadFixtures([
            '@OroVisibilityBundle/Tests/Functional/Api/DataFixtures/customer_group_product_visibilities.yml',
        ]);
    }

    public function testCreateEntities()
    {
        $this->processUpdateList(
            CustomerGroupProductVisibility::class,
            'update_list_create_customer_group_product_visibilities.yml'
        );

        $visibilityRepo = $this->getEntityManager()->getRepository(CustomerGroupProductVisibility::class);
        $visibility1 = $visibilityRepo->findOneBy([
            'product' => $this->getReference('product-4')->getId(),
            'scope'   => $this->getReference('scope_2')->getId(),
        ]);
        $visibility2 = $visibilityRepo->findOneBy([
            'product' => $this->getReference('product-4')->getId(),
            'scope'   => $this->getReference('scope_3')->getId(),
        ]);
        self::assertMessagesSent(
            Topics::RESOLVE_PRODUCT_VISIBILITY,
            [
                [
                    'entity_class_name' => CustomerGroupProductVisibility::class,
                    'id'                => $visibility1->getId(),
                ],
                [
                    'entity_class_name' => CustomerGroupProductVisibility::class,
                    'id'                => $visibility2->getId(),
                ],
            ]
        );

        $response = $this->cget(
            ['entity' => 'customergroupproductvisibilities'],
            ['filter[product][eq]' => '@product-4->id']
        );

        $this->assertResponseContains('update_list_create_customer_group_product_visibilities.yml', $response);
    }

    public function testUpdateEntities()
    {
        $visibility1Id = $this->getReference('visibility_1')->getId();
        $visibility2Id = $this->getReference('visibility_2')->getId();

        $visibility1ApiId = '<(implode("-", [@product-1->id, @customer_group.group1->id]))>';
        $visibility2ApiId = '<(implode("-", [@product-2->id, @customer_group.group2->id]))>';
        $data = [
            'data' => [
                [
                    'meta'       => ['update' => true],
                    'type'       => 'customergroupproductvisibilities',
                    'id'         => $visibility1ApiId,
                    'attributes' => [
                        'visibility' => 'hidden',
                    ],
                ],
                [
                    'meta'       => ['update' => true],
                    'type'       => 'customergroupproductvisibilities',
                    'id'         => $visibility2ApiId,
                    'attributes' => [
                        'visibility' => 'visible',
                    ],
                ],
            ],
        ];
        $this->processUpdateList(CustomerGroupProductVisibility::class, $data);

        self::assertMessagesSent(
            Topics::RESOLVE_PRODUCT_VISIBILITY,
            [
                [
                    'entity_class_name' => CustomerGroupProductVisibility::class,
                    'id'                => $visibility1Id,
                ],
                [
                    'entity_class_name' => CustomerGroupProductVisibility::class,
                    'id'                => $visibility2Id,
                ],
            ]
        );

        $response = $this->cget(
            ['entity' => 'customergroupproductvisibilities'],
            ['filter' => ['id' => [$visibility1ApiId, $visibility2ApiId]]]
        );
        $expectedData = $data;
        foreach ($expectedData['data'] as $key => $item) {
            unset($expectedData['data'][$key]['meta']);
        }
        $this->assertResponseContains($expectedData, $response);
    }

    public function testCreateAndUpdateEntities()
    {
        $visibility1ApiId = '<(implode("-", [@product-1->id, @customer_group.group1->id]))>';
        $visibility2ApiId = '<(implode("-", [@product-4->id, @customer_group.group3->id]))>';

        $data = [
            'data' => [
                [
                    'meta'       => ['update' => true],
                    'type'       => 'customergroupproductvisibilities',
                    'id'         => $visibility1ApiId,
                    'attributes' => [
                        'visibility' => 'hidden',
                    ],
                ],
                [
                    'type'          => 'customergroupproductvisibilities',
                    'attributes'    => [
                        'visibility' => 'visible',
                    ],
                    'relationships' => [
                        'product'       => [
                            'data' => [
                                'type' => 'products',
                                'id'   => '<toString(@product-4->id)>',
                            ],
                        ],
                        'customerGroup' => [
                            'data' => [
                                'type' => 'customergroups',
                                'id'   => '<toString(@customer_group.group3->id)>',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->processUpdateList(CustomerGroupProductVisibility::class, $data);

        $visibility1 = $this->getReference('visibility_1');
        $visibility2 = $this->getEntityManager()->getRepository(CustomerGroupProductVisibility::class)->findOneBy(
            [
                'product' => $this->getReference('product-4')->getId(),
                'scope'   => $this->getReference('scope_3')->getId(),
            ]
        );
        self::assertMessagesSent(
            Topics::RESOLVE_PRODUCT_VISIBILITY,
            [
                [
                    'entity_class_name' => CustomerGroupProductVisibility::class,
                    'id'                => $visibility1->getId(),
                ],
                [
                    'entity_class_name' => CustomerGroupProductVisibility::class,
                    'id'                => $visibility2->getId(),
                ],
            ]
        );

        $response = $this->cget(
            ['entity' => 'customergroupproductvisibilities'],
            ['filter' => ['id' => [$visibility1ApiId, $visibility2ApiId]]]
        );
        $expectedData['data'][1]['id'] = $visibility2ApiId;
        unset($expectedData['data'][1]['meta']);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testTryToCreateEntitiesWithIncludes()
    {
        $data = [
            'data'     => [
                [
                    'type'          => 'customergroupproductvisibilities',
                    'attributes'    => [
                        'visibility' => 'visible',
                    ],
                    'relationships' => [
                        'product'       => [
                            'data' => [
                                'type' => 'products',
                                'id'   => '<toString(@product-4->id)>',
                            ],
                        ],
                        'customerGroup' => [
                            'data' => [
                                'type' => 'customergroups',
                                'id'   => 'new_customer_group',
                            ],
                        ],
                    ],
                ],
            ],
            'included' => [
                [
                    'type'       => 'customergroups',
                    'id'         => 'new_customer_group',
                    'attributes' => [
                        'name' => 'New Customer Group',
                    ],
                ],
            ],
        ];
        $operationId = $this->processUpdateList(
            CustomerGroupProductVisibility::class,
            $data,
            false
        );

        $this->assertAsyncOperationErrors(
            [
                [
                    'id'     => $operationId . '-1-1',
                    'status' => 400,
                    'title'  => 'request data constraint',
                    'detail' => 'The included data are not supported for this resource type.',
                    'source' => ['pointer' => '/included'],
                ],
            ],
            $operationId
        );
    }
}
