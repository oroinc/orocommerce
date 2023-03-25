<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiUpdateListTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;

/**
 * @group CommunityEdition
 *
 * @dbIsolationPerTest
 */
class ProductVisibilityUpdateListTest extends RestJsonApiUpdateListTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->getOptionalListenerManager()->enableListener('oro_visibility.entity_listener.product_visibility_change');
        $this->loadFixtures([
            '@OroVisibilityBundle/Tests/Functional/Api/DataFixtures/product_visibilities.yml',
        ]);
    }

    public function testCreateEntities(): void
    {
        $product1Id = $this->getReference('product-4')->getId();
        $product2Id = $this->getReference('product-5')->getId();

        $this->processUpdateList(
            ProductVisibility::class,
            'update_list_create_product_visibilities.yml'
        );

        $response = $this->cget(
            ['entity' => 'productvisibilities'],
            ['filter' => ['id' => [$product1Id, $product2Id]]]
        );

        $responseContent = [
            'data' => [
                [
                    'type'          => 'productvisibilities',
                    'id'            => '<toString(@product-4->id)>',
                    'attributes'    => [
                        'visibility' => 'visible',
                    ],
                    'relationships' => [
                        'product' => [
                            'data' => [
                                'type' => 'products',
                                'id'   => '<toString(@product-4->id)>',
                            ],
                        ],
                    ],
                ],
                [
                    'type'          => 'productvisibilities',
                    'id'            => '<toString(@product-5->id)>',
                    'attributes'    => [
                        'visibility' => 'hidden',
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
            ],
        ];
        $this->assertResponseContains($responseContent, $response);
    }

    public function testUpdateEntities(): void
    {
        $visibility1 = $this->getReference('product_visibility_1');
        $visibility2 = $this->getReference('product_visibility_2');

        $data = [
            'data' => [
                [
                    'meta'       => ['update' => true],
                    'type'       => 'productvisibilities',
                    'id'         => (string)$visibility1->getProduct()->getId(),
                    'attributes' => [
                        'visibility' => 'hidden',
                    ],
                ],
                [
                    'meta'       => ['update' => true],
                    'type'       => 'productvisibilities',
                    'id'         => (string)$visibility2->getProduct()->getId(),
                    'attributes' => [
                        'visibility' => 'visible',
                    ],
                ],
            ],
        ];
        $this->processUpdateList(ProductVisibility::class, $data);

        $response = $this->cget(
            ['entity' => 'productvisibilities'],
            ['filter' => ['id' => [$visibility1->getProduct()->getId(), $visibility2->getProduct()->getId()]]]
        );
        $expectedData = $data;
        foreach ($expectedData['data'] as $key => $item) {
            unset($expectedData['data'][$key]['meta']);
        }
        $this->assertResponseContains($expectedData, $response);
    }

    public function testCreateAndUpdateEntities(): void
    {
        $product1Id = $this->getReference('product-1')->getId();
        $product2Id = $this->getReference('product-4')->getId();
        $data = [
            'data' => [
                [
                    'meta'       => ['update' => true],
                    'type'       => 'productvisibilities',
                    'id'         => '<toString(@product-1->id)>',
                    'attributes' => [
                        'visibility' => 'hidden',
                    ],
                ],
                [
                    'type'          => 'productvisibilities',
                    'attributes'    => [
                        'visibility' => 'visible',
                    ],
                    'relationships' => [
                        'product' => [
                            'data' => [
                                'type' => 'products',
                                'id'   => '<toString(@product-4->id)>',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->processUpdateList(ProductVisibility::class, $data);

        $response = $this->cget(
            ['entity' => 'productvisibilities'],
            ['filter' => ['id' => [$product1Id, $product2Id]]]
        );
        $expectedData['data'][1]['id'] = (string)$product2Id;
        unset($expectedData['data'][1]['meta']);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testTryToCreateEntitiesWithIncludes(): void
    {
        $data = [
            'data'     => [
                [
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
            ],
            'included' => [
                [
                    'type' => 'products',
                    'id'   => 'new_product',
                ],
            ],
        ];
        $operationId = $this->processUpdateList(
            ProductVisibility::class,
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
