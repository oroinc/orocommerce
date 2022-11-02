<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Async\Topic\ResolveProductVisibilityTopic;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group CommunityEdition
 *
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductVisibilityTest extends RestJsonApiTestCase
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
            '@OroVisibilityBundle/Tests/Functional/Api/DataFixtures/product_visibilities.yml'
        ]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'productvisibilities']
        );

        $this->assertResponseContains('cget_product_visibility.yml', $response);
    }

    public function testTryToGetListSortById(): void
    {
        $response = $this->cget(
            ['entity' => 'productvisibilities'],
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
            ['entity' => 'productvisibilities'],
            ['sort' => 'product']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productvisibilities',
                        'id'   => '<toString(@product-1->id)>',
                    ],
                    [
                        'type' => 'productvisibilities',
                        'id'   => '<toString(@product-2->id)>',
                    ],
                    [
                        'type' => 'productvisibilities',
                        'id'   => '<toString(@product-3->id)>',
                    ],
                ],
            ],
            $response
        );
    }

    public function testGetListSortByDescProduct(): void
    {
        $response = $this->cget(
            ['entity' => 'productvisibilities'],
            ['sort' => '-product']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productvisibilities',
                        'id'   => '<toString(@product-3->id)>',
                    ],
                    [
                        'type' => 'productvisibilities',
                        'id'   => '<toString(@product-2->id)>',
                    ],
                    [
                        'type' => 'productvisibilities',
                        'id'   => '<toString(@product-1->id)>',
                    ],
                ],
            ],
            $response
        );
    }

    public function testGetListFilteredById(): void
    {
        $response = $this->cget(
            ['entity' => 'productvisibilities'],
            ['filter' => ['id' => '<toString(@product-1->id)>']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productvisibilities',
                        'id'   => '<toString(@product-1->id)>',
                    ],
                ],
            ],
            $response
        );
    }

    public function testGetListFilteredByProduct(): void
    {
        $response = $this->cget(
            ['entity' => 'productvisibilities'],
            ['filter' => ['product' => '<toString(@product-1->id)>']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productvisibilities',
                        'id'   => '<toString(@product-1->id)>',
                    ],
                ],
            ],
            $response
        );
    }

    public function testCreate(): void
    {
        $product = $this->getReference('product-4');
        $requestData = [
            'data' => [
                'type'          => 'productvisibilities',
                'attributes'    => [
                    'visibility' => 'visible',
                ],
                'relationships' => [
                    'product' => [
                        'data' => [
                            'type' => 'products',
                            'id'   => (string)$product->getId(),
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->post(
            ['entity' => 'productvisibilities'],
            $requestData
        );

        $responseContent = $requestData;
        $responseContent['data']['id'] = 'new';
        $responseContent = $this->updateResponseContent($responseContent, $response);
        $this->assertResponseContains($responseContent, $response);

        $visibility = $this->getEntityManager()->getRepository(ProductVisibility::class)->findOneBy(
            [
                'product' => $product,
                'scope'   => $this->getReference('scope'),
            ]
        );
        self::assertMessagesSent(
            ResolveProductVisibilityTopic::getName(),
            [
                [
                    'entity_class_name' => ProductVisibility::class,
                    'id'                => $visibility->getId(),
                ],
            ]
        );
    }

    public function testTryToCreateVisibilityForSameProduct(): void
    {
        $requestData = [
            'data' => [
                'type'          => 'productvisibilities',
                'attributes'    => [
                    'visibility' => 'visible',
                ],
                'relationships' => [
                    'product' => [
                        'data' => [
                            'type' => 'products',
                            'id'   => '<toString(@product-1->id)>',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->post(
            ['entity' => 'productvisibilities'],
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
                'type'          => 'productvisibilities',
                'attributes'    => [
                    'visibility' => 'visible',
                ],
                'relationships' => [
                    'product' => [
                        'data' => [
                            'type' => 'products',
                            'id'   => 'new_product',
                        ],
                    ],
                ],
            ],
            'included' => [
                ['type' => 'products', 'id' => 'new_product'],
            ],
        ];

        $response = $this->post(
            ['entity' => 'productvisibilities'],
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

    public function testTryToCreateWithWrongType(): void
    {
        $requestData = [
            'data' => [
                'type'          => 'productvisibilities',
                'attributes'    => [
                    'visibility' => 'wrong',
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
            ['entity' => 'productvisibilities'],
            $requestData,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'visibility type constraint',
                'detail' => 'The value should be one of config, hidden, visible.',
                'source' => ['pointer' => '/data/attributes/visibility'],
            ],
            $response
        );
    }

    public function testTryToCreateWithoutProduct(): void
    {
        $requestData = [
            'data' => [
                'type'       => 'productvisibilities',
                'attributes' => [
                    'visibility' => 'visible',
                ],
            ],
        ];

        $response = $this->post(
            ['entity' => 'productvisibilities'],
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

    public function testTryToCreateWithoutVisibilityType(): void
    {
        $requestData = [
            'data' => [
                'type'          => 'productvisibilities',
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
            ['entity' => 'productvisibilities'],
            $requestData,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'visibility type constraint',
                'detail' => 'The value should be one of config, hidden, visible.',
                'source' => ['pointer' => '/data/attributes/visibility'],
            ],
            $response
        );
    }

    public function testUpdate(): void
    {
        $visibilityId = (string)$this->getReference('product-1')->getId();
        $requestData = [
            'data' => [
                'type'       => 'productvisibilities',
                'id'         => $visibilityId,
                'attributes' => [
                    'visibility' => 'hidden',
                ],
            ],
        ];

        $response = $this->patch(
            ['entity' => 'productvisibilities', 'id' => $visibilityId],
            $requestData
        );

        $responseContent = $requestData;
        $this->assertResponseContains($responseContent, $response);

        self::assertMessagesSent(
            ResolveProductVisibilityTopic::getName(),
            [
                [
                    'entity_class_name' => ProductVisibility::class,
                    'id'                => $this->getReference('product_visibility_1')->getId(),
                ],
            ]
        );
    }

    public function testTryToUpdateProduct(): void
    {
        $visibilityId = (string)$this->getReference('product-1')->getId();
        $requestData = [
            'data' => [
                'type'          => 'productvisibilities',
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
            ['entity' => 'productvisibilities', 'id' => $visibilityId],
            $requestData
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'productvisibilities',
                    'id'            => $visibilityId,
                    'relationships' => [
                        'product' => [
                            'data' => [
                                'type' => 'products',
                                'id'   => $visibilityId,
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
        $visibility = $this->getReference('product_visibility_1');
        $visibilityId = $visibility->getId();
        $productId = $visibility->getProduct()->getId();
        $scopeId = $visibility->getScope()->getId();

        $this->delete([
            'entity' => 'productvisibilities',
            'id'     => '<toString(@product-1->id)>',
        ]);

        self::assertNull(
            $this->getEntityManager()->find(ProductVisibility::class, $visibilityId)
        );

        self::assertMessagesSent(
            ResolveProductVisibilityTopic::getName(),
            [
                [
                    'entity_class_name' => ProductVisibility::class,
                    'target_class_name' => Product::class,
                    'target_id'         => $productId,
                    'scope_id'          => $scopeId,
                ],
            ]
        );
    }

    public function testDeleteList(): void
    {
        $visibilityId = $this->getReference('product_visibility_2')->getId();

        $this->cdelete(
            ['entity' => 'productvisibilities'],
            ['filter[product]' => '<toString(@product-2->id)>']
        );

        self::assertNull($this->getEntityManager()->find(ProductVisibility::class, $visibilityId));
    }

    public function testTryToGetSubresourceForProduct(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'productvisibilities', 'id' => '<toString(@product-1->id)>', 'association' => 'product'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToGetRelationshipForProduct(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'productvisibilities', 'id' => '<toString(@product-1->id)>', 'association' => 'product'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToUpdateRelationshipForProduct(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'productvisibilities', 'id' => '<toString(@product-1->id)>', 'association' => 'product'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }
}
