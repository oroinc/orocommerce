<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Async\Topic\ResolveProductVisibilityTopic;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group CommunityEdition
 *
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CustomerGroupProductVisibilityTest extends RestJsonApiTestCase
{
    use MessageQueueExtension;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->getOptionalListenerManager()->enableListener('oro_visibility.entity_listener.product_visibility_change');
        $this->loadFixtures([
            '@OroVisibilityBundle/Tests/Functional/Api/DataFixtures/customer_group_product_visibilities.yml'
        ]);
    }

    private function getId(string $product, string $customerGroup): string
    {
        return sprintf(
            '%s-%s',
            $this->getReference($product)->getId(),
            $this->getReference($customerGroup)->getId()
        );
    }

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'customergroupproductvisibilities']
        );

        $this->assertResponseContains('cget_customer_group_product_visibility.yml', $response);
    }

    public function testTryToGetListSortById(): void
    {
        $response = $this->cget(
            ['entity' => 'customergroupproductvisibilities'],
            ['sort' => 'id'],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'sort constraint',
                'detail' => 'Sorting by "id" field is not supported.',
                'source' => ['parameter' => 'sort'],
            ],
            $response
        );
    }

    public function testGetListSortByProduct(): void
    {
        $response = $this->cget(
            ['entity' => 'customergroupproductvisibilities'],
            ['sort' => 'product']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'customergroupproductvisibilities',
                        'id'   => '<(implode("-", [@product-1->id, @customer_group.group1->id]))>',
                    ],
                    [
                        'type' => 'customergroupproductvisibilities',
                        'id'   => '<(implode("-", [@product-2->id, @customer_group.group2->id]))>',
                    ],
                    [
                        'type' => 'customergroupproductvisibilities',
                        'id'   => '<(implode("-", [@product-3->id, @customer_group.group3->id]))>',
                    ],
                ],
            ],
            $response
        );
    }

    public function testGetListSortByDescProduct(): void
    {
        $response = $this->cget(
            ['entity' => 'customergroupproductvisibilities'],
            ['sort' => '-product']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'customergroupproductvisibilities',
                        'id'   => '<(implode("-", [@product-3->id, @customer_group.group3->id]))>',
                    ],
                    [
                        'type' => 'customergroupproductvisibilities',
                        'id'   => '<(implode("-", [@product-2->id, @customer_group.group2->id]))>',
                    ],
                    [
                        'type' => 'customergroupproductvisibilities',
                        'id'   => '<(implode("-", [@product-1->id, @customer_group.group1->id]))>',
                    ],
                ],
            ],
            $response
        );
    }

    public function testGetListSortByCustomerGroup(): void
    {
        $response = $this->cget(
            ['entity' => 'customergroupproductvisibilities'],
            ['sort' => 'customerGroup']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'customergroupproductvisibilities',
                        'id'   => '<(implode("-", [@product-1->id, @customer_group.group1->id]))>',
                    ],
                    [
                        'type' => 'customergroupproductvisibilities',
                        'id'   => '<(implode("-", [@product-2->id, @customer_group.group2->id]))>',
                    ],
                    [
                        'type' => 'customergroupproductvisibilities',
                        'id'   => '<(implode("-", [@product-3->id, @customer_group.group3->id]))>',
                    ],

                ],
            ],
            $response
        );
    }

    public function testGetListSortByDescCustomerGroup(): void
    {
        $response = $this->cget(
            ['entity' => 'customergroupproductvisibilities'],
            ['sort' => '-customerGroup']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'customergroupproductvisibilities',
                        'id'   => '<(implode("-", [@product-3->id, @customer_group.group3->id]))>',
                    ],
                    [
                        'type' => 'customergroupproductvisibilities',
                        'id'   => '<(implode("-", [@product-2->id, @customer_group.group2->id]))>',
                    ],
                    [
                        'type' => 'customergroupproductvisibilities',
                        'id'   => '<(implode("-", [@product-1->id, @customer_group.group1->id]))>',
                    ],
                ],
            ],
            $response
        );
    }

    public function testGetListFilteredById(): void
    {
        $visibilityId = $this->getId('product-1', 'customer_group.group1');

        $response = $this->cget(
            ['entity' => 'customergroupproductvisibilities'],
            ['filter' => ['id' => $visibilityId]]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'customergroupproductvisibilities',
                        'id'   => '<(implode("-", [@product-1->id, @customer_group.group1->id]))>',
                    ],
                ],
            ],
            $response
        );
    }

    public function testGetListFilteredByProduct(): void
    {
        $response = $this->cget(
            ['entity' => 'customergroupproductvisibilities'],
            ['filter' => ['product' => '<toString(@product-1->id)>']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'customergroupproductvisibilities',
                        'id'   => '<(implode("-", [@product-1->id, @customer_group.group1->id]))>',
                    ],
                ],
            ],
            $response
        );
    }

    public function testGetListFilteredByCustomerGroup(): void
    {
        $response = $this->cget(
            ['entity' => 'customergroupproductvisibilities'],
            ['filter' => ['customerGroup' => '<toString(@customer_group.group1->id)>']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'customergroupproductvisibilities',
                        'id'   => '<(implode("-", [@product-1->id, @customer_group.group1->id]))>',
                    ],
                ],
            ],
            $response
        );
    }

    public function testCreate(): void
    {
        $requestData = [
            'data' => [
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
                            'id'   => '<toString(@customer_group.anonymous->id)>',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->post(
            ['entity' => 'customergroupproductvisibilities'],
            $requestData
        );

        $responseContent = $requestData;
        $responseContent['data']['id'] = '<(implode("-", [@product-4->id, @customer_group.anonymous->id]))>';
        $responseContent = $this->updateResponseContent($responseContent, $response);
        $this->assertResponseContains($responseContent, $response);

        $visibility = $this->getEntityManager()->getRepository(CustomerGroupProductVisibility::class)->findOneBy(
            [
                'product' => $this->getReference('product-4')->getId(),
            ]
        );
        self::assertMessagesSent(
            ResolveProductVisibilityTopic::getName(),
            [
                [
                    'entity_class_name' => CustomerGroupProductVisibility::class,
                    'id'                => $visibility->getId(),
                ],
            ]
        );
    }

    public function testTryToCreateVisibilityForSameProductAndCustomerGroup(): void
    {
        $requestData = [
            'data' => [
                'type'          => 'customergroupproductvisibilities',
                'attributes'    => [
                    'visibility' => 'visible',
                ],
                'relationships' => [
                    'product'       => [
                        'data' => [
                            'type' => 'products',
                            'id'   => '<toString(@product-1->id)>',
                        ],
                    ],
                    'customerGroup' => [
                        'data' => [
                            'type' => 'customergroups',
                            'id'   => '<toString(@customer_group.group1->id)>',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->post(
            ['entity' => 'customergroupproductvisibilities'],
            $requestData,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'conflict constraint',
                'detail' => 'The visibility entity already exists.',
            ],
            $response,
            Response::HTTP_CONFLICT
        );
    }

    public function testTryToCreateWithIncludes(): void
    {
        $requestData = [
            'data' => [
                'type'          => 'customergroupproductvisibilities',
                'attributes'    => [
                    'visibility' => 'visible',
                ],
                'relationships' => [
                    'product'       => [
                        'data' => [
                            'type' => 'products',
                            'id'   => 'new_product',
                        ],
                    ],
                    'customerGroup' => [
                        'data' => [
                            'type' => 'customergroups',
                            'id'   => '<toString(@customer_group.group1->id)>',
                        ],
                    ],
                ],
            ],
            'included' => [
                ['type' => 'products', 'id' => 'new_product'],
            ],
        ];

        $response = $this->post(
            ['entity' => 'customergroupproductvisibilities'],
            $requestData,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'request data constraint',
                'detail' => 'The included data are not supported for this resource type.',
                'source' => ['pointer' => '/included']
            ],
            $response
        );
    }

    public function testUpdate(): void
    {
        $visibilityId = $this->getId('product-1', 'customer_group.group1');
        $requestData = [
            'data' => [
                'type'       => 'customergroupproductvisibilities',
                'id'         => $visibilityId,
                'attributes' => [
                    'visibility' => 'hidden',
                ],
            ],
        ];

        $response = $this->patch(
            ['entity' => 'customergroupproductvisibilities', 'id' => $visibilityId],
            $requestData
        );

        $responseContent = $requestData;
        $this->assertResponseContains($responseContent, $response);

        self::assertMessagesSent(
            ResolveProductVisibilityTopic::getName(),
            [
                [
                    'entity_class_name' => CustomerGroupProductVisibility::class,
                    'id'                => $this->getReference('visibility_1')->getId(),
                ],
            ]
        );
    }

    public function testTryToCreateWithWrongType(): void
    {
        $requestData = [
            'data' => [
                'type'          => 'customergroupproductvisibilities',
                'attributes'    => [
                    'visibility' => 'wrong',
                ],
                'relationships' => [
                    'product'       => [
                        'data' => [
                            'type' => 'products',
                            'id'   => '<toString(@product-5->id)>',
                        ],
                    ],
                    'customerGroup' => [
                        'data' => [
                            'type' => 'customergroups',
                            'id'   => '<toString(@customer_group.anonymous->id)>',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->post(
            ['entity' => 'customergroupproductvisibilities'],
            $requestData,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'visibility type constraint',
                'detail' => 'The value should be one of current_product, hidden, visible.',
                'source' => ['pointer' => '/data/attributes/visibility'],
            ],
            $response
        );
    }

    public function testTryToCreateWithoutProduct(): void
    {
        $requestData = [
            'data' => [
                'type'          => 'customergroupproductvisibilities',
                'attributes'    => [
                    'visibility' => 'visible',
                ],
                'relationships' => [
                    'customerGroup' => [
                        'data' => [
                            'type' => 'customergroups',
                            'id'   => '<toString(@customer_group.anonymous->id)>',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->post(
            ['entity' => 'customergroupproductvisibilities'],
            $requestData,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'not null constraint',
                'detail' => 'This value should not be null.',
                'source' => ['pointer' => '/data/relationships/product/data'],
            ],
            $response
        );
    }

    public function testTryToCreateWithoutCustomerGroup(): void
    {
        $requestData = [
            'data' => [
                'type'          => 'customergroupproductvisibilities',
                'attributes'    => [
                    'visibility' => 'visible',
                ],
                'relationships' => [
                    'product' => [
                        'data' => [
                            'type' => 'products',
                            'id'   => '<toString(@product-5->id)>',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->post(
            ['entity' => 'customergroupproductvisibilities'],
            $requestData,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'not null constraint',
                'detail' => 'This value should not be null.',
                'source' => ['pointer' => '/data/relationships/customerGroup/data'],
            ],
            $response
        );
    }

    public function testTryToCreateWithoutVisibilityType(): void
    {
        $requestData = [
            'data' => [
                'type'          => 'customergroupproductvisibilities',
                'relationships' => [
                    'product'       => [
                        'data' => [
                            'type' => 'products',
                            'id'   => '<toString(@product-5->id)>',
                        ],
                    ],
                    'customerGroup' => [
                        'data' => [
                            'type' => 'customergroups',
                            'id'   => '<toString(@customer_group.anonymous->id)>',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->post(
            ['entity' => 'customergroupproductvisibilities'],
            $requestData,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'visibility type constraint',
                'detail' => 'The value should be one of current_product, hidden, visible.',
                'source' => ['pointer' => '/data/attributes/visibility'],
            ],
            $response
        );
    }

    public function testTryToUpdateProduct(): void
    {
        $visibilityId = $this->getId('product-1', 'customer_group.group1');
        $requestData = [
            'data' => [
                'type'          => 'customergroupproductvisibilities',
                'id'            => $visibilityId,
                'relationships' => [
                    'product' => [
                        'data' => [
                            'type' => 'products',
                            'id'   => '<toString(@product-2->id)>',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->patch(
            ['entity' => 'customergroupproductvisibilities', 'id' => $visibilityId],
            $requestData
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'customergroupproductvisibilities',
                    'id'            => $visibilityId,
                    'relationships' => [
                        'product' => [
                            'data' => [
                                'type' => 'products',
                                'id'   => '<toString(@product-1->id)>',
                            ],
                        ],
                    ],
                ],
            ],
            $response
        );
    }

    public function testTryToUpdateCustomerGroup(): void
    {
        $visibilityId = $this->getId('product-1', 'customer_group.group1');
        $requestData = [
            'data' => [
                'type'          => 'customergroupproductvisibilities',
                'id'            => $visibilityId,
                'relationships' => [
                    'customerGroup' => [
                        'data' => [
                            'type' => 'customergroups',
                            'id'   => '<toString(@customer_group.anonymous->id)>',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->patch(
            ['entity' => 'customergroupproductvisibilities', 'id' => $visibilityId],
            $requestData
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'customergroupproductvisibilities',
                    'id'            => $visibilityId,
                    'relationships' => [
                        'customerGroup' => [
                            'data' => [
                                'type' => 'customergroups',
                                'id'   => '<toString(@customer_group.group1->id)>',
                            ],
                        ],
                    ],
                ],
            ],
            $response
        );
    }

    public function testDelete(): void
    {
        $visibility = $this->getReference('visibility_1');
        $productId = $visibility->getProduct()->getId();
        $scope = $visibility->getScope();

        $visibilityApiId = $productId . '-' . $scope->getCustomerGroup()->getId();
        $visibilityId = $visibility->getId();

        $this->delete([
            'entity' => 'customergroupproductvisibilities',
            'id'     => $visibilityApiId,
        ]);

        self::assertNull(
            $this->getEntityManager()->find(CustomerGroupProductVisibility::class, $visibilityId)
        );

        self::assertMessagesSent(
            ResolveProductVisibilityTopic::getName(),
            [
                [
                    'entity_class_name' => CustomerGroupProductVisibility::class,
                    'target_class_name' => Product::class,
                    'target_id'         => $productId,
                    'scope_id'          => $scope->getId(),
                ],
            ]
        );
    }

    public function testDeleteList(): void
    {
        $visibilityId = $this->getReference('visibility_2')->getId();
        $this->cdelete(
            ['entity' => 'customergroupproductvisibilities'],
            ['filter[product]' => '<toString(@product-2->id)>']
        );

        self::assertNull($this->getEntityManager()->find(CustomerGroupProductVisibility::class, $visibilityId));
    }

    public function testTryToGetSubresourceForProduct(): void
    {
        $visibilityId = $this->getId('product-1', 'customer_group.group1');
        $response = $this->getSubresource(
            ['entity' => 'customergroupproductvisibilities', 'id' => $visibilityId, 'association' => 'product'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToGetRelationshipForProduct(): void
    {
        $visibilityId = $this->getId('product-1', 'customer_group.group1');
        $response = $this->getRelationship(
            ['entity' => 'customergroupproductvisibilities', 'id' => $visibilityId, 'association' => 'product'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToUpdateRelationshipForProduct(): void
    {
        $visibilityId = $this->getId('product-1', 'customer_group.group1');
        $response = $this->patchRelationship(
            ['entity' => 'customergroupproductvisibilities', 'id' => $visibilityId, 'association' => 'product'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToGetSubresourceForCustomerGroup(): void
    {
        $visibilityId = $this->getId('product-1', 'customer_group.group1');
        $response = $this->getSubresource(
            ['entity' => 'customergroupproductvisibilities', 'id' => $visibilityId, 'association' => 'customergroup'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToGetRelationshipForCustomerGroup(): void
    {
        $visibilityId = $this->getId('product-1', 'customer_group.group1');
        $response = $this->getRelationship(
            ['entity' => 'customergroupproductvisibilities', 'id' => $visibilityId, 'association' => 'customergroup'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToUpdateRelationshipForCustomerGroup(): void
    {
        $visibilityId = $this->getId('product-1', 'customer_group.group1');
        $response = $this->patchRelationship(
            ['entity' => 'customergroupproductvisibilities', 'id' => $visibilityId, 'association' => 'customergroup'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }
}
