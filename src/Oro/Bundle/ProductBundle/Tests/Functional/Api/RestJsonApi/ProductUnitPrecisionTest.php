<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class ProductUnitPrecisionTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroProductBundle/Tests/Functional/Api/DataFixtures/product.yml',
        ]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'productunitprecisions']);
        $this->assertResponseContains('cget_product_unit_precisions.yml', $response);
    }

    public function testGetListFilteredByProduct(): void
    {
        $response = $this->cget(
            ['entity' => 'productunitprecisions'],
            ['filter[product]' => '<toString(@product1->id)>']
        );

        $this->assertResponseContains('cget_product_unit_precisions_by_product.yml', $response);
    }

    public function testGet(): void
    {
        $response = $this->get(
            [
                'entity' => 'productunitprecisions',
                'id' => '<toString(@product1_precision->id)>'
            ]
        );

        $this->assertResponseContains('get_product_unit_precision.yml', $response);
    }

    public function testCreate(): void
    {
        $response = $this->post(
            ['entity' => 'productunitprecisions'],
            'create_product_unit_precision.yml'
        );

        $responseContent = $this->updateResponseContent('create_product_unit_precision.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testTryToCreateWithExistingProductUnit(): void
    {
        $response = $this->post(
            ['entity' => 'productunitprecisions'],
            'create_product_unit_precision_exising_unit.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'unique entity constraint',
                'detail' => 'This value is already used.',
                'source' => [
                    'pointer' => '/data/relationships/product/data'
                ]
            ],
            $response,
            Response::HTTP_BAD_REQUEST
        );
    }

    public function testUpdate(): void
    {
        $response = $this->patch(
            [
                'entity' => 'productunitprecisions',
                'id' => '<toString(@product1_precision2->id)>'
            ],
            ['data' => [
                'type'       => 'productunitprecisions',
                'id'         => '<toString(@product1_precision2->id)>',
                'attributes' => [
                    'conversionRate' => 10,
                    'sell' => false
                ]
            ]]
        );

        $this->assertResponseContains('update_product_unit_precision.yml', $response);
    }

    public function testTryToUpdateUnitWhenReferencedByProductKitItem(): void
    {
        $response = $this->patch(
            [
                'entity' => 'productunitprecisions',
                'id' => '<toString(@product1_precision->id)>',
            ],
            [
                'data' => [
                    'type' => 'productunitprecisions',
                    'id' => '<toString(@product1_precision->id)>',
                    'relationships' => [
                        'unit' => [
                            'data' => [
                                'type' => 'productunits',
                                'id' => 'hour',
                            ],
                        ],
                    ],
                ],
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'product unit precision referenced by product kit items constraint',
                'detail' => 'Unit cannot be changed because it is used in the following product kits: '
                    . 'PKSKU3, PKSKU1.',
                'source' => [
                    'pointer' => '/data/relationships/unit/data',
                ],
            ],
            $response
        );
    }

    public function testDelete(): void
    {
        $id = $this->getReference('product1_precision2')->getId();
        $this->delete(
            [
                'entity' => 'productunitprecisions',
                'id' => (string)$id
            ]
        );

        $this->assertNull($this->getEntityManager()->find(ProductUnitPrecision::class, $id));
    }

    public function testTryToDeletePrimaryProductUnitPrecision(): void
    {
        $response = $this->delete(
            [
                'entity' => 'productunitprecisions',
                'id' => '<toString(@product1_precision->id)>'
            ],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'The delete operation is forbidden. Reason: primary precision.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToDeleteProductUnitPrecisionReferencedByProductKitItem(): void
    {
        $response = $this->delete(
            [
                'entity' => 'productunitprecisions',
                'id' => '<toString(@product1_precision1->id)>'
            ],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'The delete operation is forbidden. Reason: Product unit "set" cannot be removed '
                    . 'because it is used in the following product kits: PKSKU2.',
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testDeleteList(): void
    {
        $id = $this->getReference('product1_precision2')->getId();
        $id1 = $this->getReference('product1_precision3')->getId();

        $this->cdelete(
            ['entity' => 'productunitprecisions'],
            [
                'filter' => [
                    'id' => [$id, $id1]
                ]
            ]
        );

        $this->assertNull($this->getEntityManager()->find(ProductUnitPrecision::class, $id));
        $this->assertNull($this->getEntityManager()->find(ProductUnitPrecision::class, $id1));
    }

    public function testGetSubresourceForProduct(): void
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'productunitprecisions',
                'id'          => '<toString(@product1_precision->id)>',
                'association' => 'product'
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'products',
                    'id'         => '<toString(@product1->id)>',
                    'attributes' => [
                        'sku' => '<toString(@product1->sku)>'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForProduct(): void
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'productunitprecisions',
                'id'          => '<toString(@product1_precision->id)>',
                'association' => 'product'
            ]
        );

        $this->assertResponseContains(
            [
                'data' => ['type' => 'products', 'id' => '<toString(@product1->id)>']
            ],
            $response
        );
    }

    public function testGetSubresourceForUnit(): void
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'productunitprecisions',
                'id'          => '<toString(@product1_precision->id)>',
                'association' => 'unit'
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'productunits',
                    'id'         => '<toString(@item->code)>',
                    'attributes' => [
                        'label' => 'item'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForUnit(): void
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'productunitprecisions',
                'id'          => '<toString(@product1_precision->id)>',
                'association' => 'unit'
            ]
        );

        $this->assertResponseContains(
            [
                'data' => ['type' => 'productunits', 'id' => '<toString(@item->code)>']
            ],
            $response
        );
    }
}
