<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Async\Topic\ResolveProductVisibilityTopic;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group CommunityEdition
 *
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CustomerProductVisibilityTest extends RestJsonApiTestCase
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
            '@OroVisibilityBundle/Tests/Functional/Api/DataFixtures/customer_product_visibilities.yml'
        ]);
    }

    private function getId(string $product, string $customer): string
    {
        return sprintf(
            '%s-%s',
            $this->getReference($product)->getId(),
            $this->getReference($customer)->getId()
        );
    }

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'customerproductvisibilities']
        );

        $this->assertResponseContains('cget_customer_product_visibility.yml', $response);
    }

    public function testTryToGetListSortById(): void
    {
        $response = $this->cget(
            ['entity' => 'customerproductvisibilities'],
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
            ['entity' => 'customerproductvisibilities'],
            ['sort' => 'product']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'customerproductvisibilities',
                        'id'   => '<(implode("-", [@product-1->id, @customer.orphan->id]))>',
                    ],
                    [
                        'type' => 'customerproductvisibilities',
                        'id'   => '<(implode("-", [@product-2->id, @customer.level_1_1->id]))>',
                    ],
                    [
                        'type' => 'customerproductvisibilities',
                        'id'   => '<(implode("-", [@product-3->id, @customer.level_1.1->id]))>',
                    ],
                ],
            ],
            $response
        );
    }

    public function testGetListSortByDescProduct(): void
    {
        $response = $this->cget(
            ['entity' => 'customerproductvisibilities'],
            ['sort' => '-product']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'customerproductvisibilities',
                        'id'   => '<(implode("-", [@product-3->id, @customer.level_1.1->id]))>',
                    ],
                    [
                        'type' => 'customerproductvisibilities',
                        'id'   => '<(implode("-", [@product-2->id, @customer.level_1_1->id]))>',
                    ],
                    [
                        'type' => 'customerproductvisibilities',
                        'id'   => '<(implode("-", [@product-1->id, @customer.orphan->id]))>',
                    ],
                ],
            ],
            $response
        );
    }

    public function testGetListSortByCustomer(): void
    {
        $response = $this->cget(
            ['entity' => 'customerproductvisibilities'],
            ['sort' => 'customer']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'customerproductvisibilities',
                        'id'   => '<(implode("-", [@product-1->id, @customer.orphan->id]))>',
                    ],
                    [
                        'type' => 'customerproductvisibilities',
                        'id'   => '<(implode("-", [@product-3->id, @customer.level_1.1->id]))>',
                    ],
                    [
                        'type' => 'customerproductvisibilities',
                        'id'   => '<(implode("-", [@product-2->id, @customer.level_1_1->id]))>',
                    ],
                ],
            ],
            $response
        );
    }

    public function testGetListSortByDescCustomer(): void
    {
        $response = $this->cget(
            ['entity' => 'customerproductvisibilities'],
            ['sort' => '-customer']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'customerproductvisibilities',
                        'id'   => '<(implode("-", [@product-2->id, @customer.level_1_1->id]))>',
                    ],
                    [
                        'type' => 'customerproductvisibilities',
                        'id'   => '<(implode("-", [@product-3->id, @customer.level_1.1->id]))>',
                    ],
                    [
                        'type' => 'customerproductvisibilities',
                        'id'   => '<(implode("-", [@product-1->id, @customer.orphan->id]))>',
                    ],
                ],
            ],
            $response
        );
    }

    public function testGetListFilteredById(): void
    {
        $visibilityId = $this->getId('product-1', 'customer.orphan');

        $response = $this->cget(
            ['entity' => 'customerproductvisibilities'],
            ['filter' => ['id' => $visibilityId]]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'customerproductvisibilities',
                        'id'   => '<(implode("-", [@product-1->id, @customer.orphan->id]))>',
                    ],
                ],
            ],
            $response
        );
    }

    public function testGetListFilteredByProduct(): void
    {
        $response = $this->cget(
            ['entity' => 'customerproductvisibilities'],
            ['filter' => ['product' => '<toString(@product-1->id)>']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'customerproductvisibilities',
                        'id'   => '<(implode("-", [@product-1->id, @customer.orphan->id]))>',
                    ],
                ],
            ],
            $response
        );
    }

    public function testGetListFilteredByCustomer(): void
    {
        $response = $this->cget(
            ['entity' => 'customerproductvisibilities'],
            ['filter' => ['customer' => '<toString(@customer.orphan->id)>']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'customerproductvisibilities',
                        'id'   => '<(implode("-", [@product-1->id, @customer.orphan->id]))>',
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
                'type'          => 'customerproductvisibilities',
                'attributes'    => [
                    'visibility' => 'visible',
                ],
                'relationships' => [
                    'product'  => [
                        'data' => [
                            'type' => 'products',
                            'id'   => '<toString(@product-4->id)>',
                        ],
                    ],
                    'customer' => [
                        'data' => [
                            'type' => 'customers',
                            'id'   => '<toString(@customer.level_1->id)>',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->post(
            ['entity' => 'customerproductvisibilities'],
            $requestData
        );

        $responseContent = $requestData;
        $responseContent['data']['id'] = '<(implode("-", [@product-4->id, @customer.level_1->id]))>';
        $responseContent = $this->updateResponseContent($responseContent, $response);
        $this->assertResponseContains($responseContent, $response);

        $visibility = $this->getEntityManager()->getRepository(CustomerProductVisibility::class)->findOneBy(
            [
                'product' => $this->getReference('product-4')->getId(),
            ]
        );
        self::assertMessagesSent(
            ResolveProductVisibilityTopic::getName(),
            [
                [
                    'entity_class_name' => CustomerProductVisibility::class,
                    'id'                => $visibility->getId(),
                ],
            ]
        );
    }

    public function testTryToCreateVisibilityForSameProductAndCustomer(): void
    {
        $requestData = [
            'data' => [
                'type'          => 'customerproductvisibilities',
                'attributes'    => [
                    'visibility' => 'visible',
                ],
                'relationships' => [
                    'product'  => [
                        'data' => [
                            'type' => 'products',
                            'id'   => '<toString(@product-1->id)>',
                        ],
                    ],
                    'customer' => [
                        'data' => [
                            'type' => 'customers',
                            'id'   => '<toString(@customer.orphan->id)>',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->post(
            ['entity' => 'customerproductvisibilities'],
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
                'type'          => 'customerproductvisibilities',
                'attributes'    => [
                    'visibility' => 'visible',
                ],
                'relationships' => [
                    'product'  => [
                        'data' => [
                            'type' => 'products',
                            'id'   => 'new_product',
                        ],
                    ],
                    'customer' => [
                        'data' => [
                            'type' => 'customers',
                            'id'   => '<toString(@customer.orphan->id)>',
                        ],
                    ],
                ],
            ],
            'included' => [
                ['type' => 'products', 'id' => 'new_product'],
            ],
        ];

        $response = $this->post(
            ['entity' => 'customerproductvisibilities'],
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
        $visibilityId = $this->getId('product-1', 'customer.orphan');
        $requestData = [
            'data' => [
                'type'       => 'customerproductvisibilities',
                'id'         => $visibilityId,
                'attributes' => [
                    'visibility' => 'hidden',
                ],
            ],
        ];

        $response = $this->patch(
            ['entity' => 'customerproductvisibilities', 'id' => $visibilityId],
            $requestData
        );

        $responseContent = $requestData;
        $this->assertResponseContains($responseContent, $response);

        self::assertMessagesSent(
            ResolveProductVisibilityTopic::getName(),
            [
                [
                    'entity_class_name' => CustomerProductVisibility::class,
                    'id'                => $this->getReference('visibility_1')->getId(),
                ],
            ]
        );
    }

    public function testTryToCreateWithWrongType(): void
    {
        $requestData = [
            'data' => [
                'type'          => 'customerproductvisibilities',
                'attributes'    => [
                    'visibility' => 'wrong',
                ],
                'relationships' => [
                    'product'  => [
                        'data' => [
                            'type' => 'products',
                            'id'   => '<toString(@product-5->id)>',
                        ],
                    ],
                    'customer' => [
                        'data' => [
                            'type' => 'customers',
                            'id'   => '<toString(@customer.level_1.4.1.1->id)>',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->post(
            ['entity' => 'customerproductvisibilities'],
            $requestData,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'visibility type constraint',
                'detail' => 'The value should be one of customer_group, current_product, hidden, visible.',
                'source' => ['pointer' => '/data/attributes/visibility'],
            ],
            $response
        );
    }

    public function testTryToCreateWithoutProduct(): void
    {
        $requestData = [
            'data' => [
                'type'          => 'customerproductvisibilities',
                'attributes'    => [
                    'visibility' => 'visible',
                ],
                'relationships' => [
                    'customer' => [
                        'data' => [
                            'type' => 'customers',
                            'id'   => '<toString(@customer.level_1.4.1.1->id)>',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->post(
            ['entity' => 'customerproductvisibilities'],
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

    public function testTryToCreateWithoutCustomer(): void
    {
        $requestData = [
            'data' => [
                'type'          => 'customerproductvisibilities',
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
            ['entity' => 'customerproductvisibilities'],
            $requestData,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'not null constraint',
                'detail' => 'This value should not be null.',
                'source' => ['pointer' => '/data/relationships/customer/data'],
            ],
            $response
        );
    }

    public function testTryToCreateWithoutVisibilityType(): void
    {
        $requestData = [
            'data' => [
                'type'          => 'customerproductvisibilities',
                'relationships' => [
                    'product'  => [
                        'data' => [
                            'type' => 'products',
                            'id'   => '<toString(@product-5->id)>',
                        ],
                    ],
                    'customer' => [
                        'data' => [
                            'type' => 'customers',
                            'id'   => '<toString(@customer.level_1.4.1.1->id)>',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->post(
            ['entity' => 'customerproductvisibilities'],
            $requestData,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'visibility type constraint',
                'detail' => 'The value should be one of customer_group, current_product, hidden, visible.',
                'source' => ['pointer' => '/data/attributes/visibility'],
            ],
            $response
        );
    }

    public function testTryToUpdateProduct(): void
    {
        $visibilityId = $this->getId('product-1', 'customer.orphan');
        $requestData = [
            'data' => [
                'type'          => 'customerproductvisibilities',
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
            ['entity' => 'customerproductvisibilities', 'id' => $visibilityId],
            $requestData
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'customerproductvisibilities',
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

    public function testTryToUpdateCustomer(): void
    {
        $visibilityId = $this->getId('product-1', 'customer.orphan');
        $requestData = [
            'data' => [
                'type'          => 'customerproductvisibilities',
                'id'            => $visibilityId,
                'relationships' => [
                    'customer' => [
                        'data' => [
                            'type' => 'customers',
                            'id'   => '<toString(@customer.level_1.1->id)>',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->patch(
            ['entity' => 'customerproductvisibilities', 'id' => $visibilityId],
            $requestData
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'customerproductvisibilities',
                    'id'            => $visibilityId,
                    'relationships' => [
                        'customer' => [
                            'data' => [
                                'type' => 'customers',
                                'id'   => '<toString(@customer.orphan->id)>',
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

        $visibilityApiId = $productId . '-' . $scope->getCustomer()->getId();
        $visibilityId = $visibility->getId();

        $this->delete(['entity' => 'customerproductvisibilities', 'id' => $visibilityApiId]);

        self::assertNull(
            $this->getEntityManager()->find(CustomerProductVisibility::class, $visibilityId)
        );

        self::assertMessagesSent(
            ResolveProductVisibilityTopic::getName(),
            [
                [
                    'entity_class_name' => CustomerProductVisibility::class,
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
            ['entity' => 'customerproductvisibilities'],
            ['filter[product]' => '<toString(@product-2->id)>']
        );

        self::assertNull($this->getEntityManager()->find(CustomerProductVisibility::class, $visibilityId));
    }

    public function testTryToGetSubresourceForProduct(): void
    {
        $visibilityId = $this->getId('product-1', 'customer.orphan');
        $response = $this->getSubresource(
            ['entity' => 'customerproductvisibilities', 'id' => $visibilityId, 'association' => 'product'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToGetRelationshipForProduct(): void
    {
        $visibilityId = $this->getId('product-1', 'customer.orphan');
        $response = $this->getRelationship(
            ['entity' => 'customerproductvisibilities', 'id' => $visibilityId, 'association' => 'product'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToUpdateRelationshipForProduct(): void
    {
        $visibilityId = $this->getId('product-1', 'customer.orphan');
        $response = $this->patchRelationship(
            ['entity' => 'customerproductvisibilities', 'id' => $visibilityId, 'association' => 'product'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToGetSubresourceForCustomer(): void
    {
        $visibilityId = $this->getId('product-1', 'customer.orphan');
        $response = $this->getSubresource(
            ['entity' => 'customerproductvisibilities', 'id' => $visibilityId, 'association' => 'customer'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToGetRelationshipForCustomer(): void
    {
        $visibilityId = $this->getId('product-1', 'customer.orphan');
        $response = $this->getRelationship(
            ['entity' => 'customerproductvisibilities', 'id' => $visibilityId, 'association' => 'customer'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToUpdateRelationshipForCustomer(): void
    {
        $visibilityId = $this->getId('product-1', 'customer.orphan');
        $response = $this->patchRelationship(
            ['entity' => 'customerproductvisibilities', 'id' => $visibilityId, 'association' => 'customer'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }
}
