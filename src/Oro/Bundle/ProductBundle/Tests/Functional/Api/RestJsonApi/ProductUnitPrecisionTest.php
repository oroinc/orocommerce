<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
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
            LoadProductUnitPrecisions::class,
            LoadProductData::class
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(['entity' => 'productunitprecisions']);
        $this->assertResponseContains('cget_product_unit_precisions.yml', $response);
    }

    public function testGetListFilteredByProduct()
    {
        $response = $this->cget(
            ['entity' => 'productunitprecisions'],
            ['filter[product]' => '<toString(@product-1->id)>']
        );

        $this->assertResponseContains('cget_product_unit_precisions_by_product.yml', $response);
    }

    public function testGet()
    {
        $response = $this->get(
            [
                'entity' => 'productunitprecisions',
                'id' => '<toString(@product_unit_precision.product-1.milliliter->id)>'
            ]
        );

        $this->assertResponseContains('get_product_unit_precision.yml', $response);
    }

    public function testCreate()
    {
        $response = $this->post(
            ['entity' => 'productunitprecisions'],
            'create_product_unit_precision.yml'
        );

        $responseContent = $this->updateResponseContent('create_product_unit_precision.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testTryToCreateWithExistingProductUnit()
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

    public function testUpdate()
    {
        $response = $this->patch(
            [
                'entity' => 'productunitprecisions',
                'id' => '<toString(@product_unit_precision.product-1.milliliter->id)>'
            ],
            ['data' => [
                'type'       => 'productunitprecisions',
                'id'         => '<toString(@product_unit_precision.product-1.milliliter->id)>',
                'attributes' => [
                    'conversionRate' => 10,
                    'sell' => false
                ]
            ]]
        );

        $this->assertResponseContains('update_product_unit_prcision.yml', $response);
    }

    public function testDelete()
    {
        $id = $this->getReference('product_unit_precision.product-1.liter')->getId();
        $this->delete(
            [
                'entity' => 'productunitprecisions',
                'id' => (string)$id
            ]
        );

        $this->assertNull($this->getEntityManager()->find(ProductUnitPrecision::class, $id));
    }

    public function testTryToDeletePrimaryProductUnitPrecision()
    {
        $response = $this->delete(
            [
                'entity' => 'productunitprecisions',
                'id' => '<toString(@product_unit_precision.product-1.milliliter->id)>'
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

    public function testDeleteList()
    {
        $id = $this->getReference('product_unit_precision.product-1.liter')->getId();
        $id1 = $this->getReference('product_unit_precision.product-1.bottle')->getId();

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

    public function testGetSubresourceForProduct()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'productunitprecisions',
                'id'          => '<toString(@product_unit_precision.product-1.milliliter->id)>',
                'association' => 'product'
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'products',
                    'id'         => '<toString(@product-1->id)>',
                    'attributes' => [
                        'sku' => 'product-1'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForProduct()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'productunitprecisions',
                'id'          => '<toString(@product_unit_precision.product-1.milliliter->id)>',
                'association' => 'product'
            ]
        );

        $this->assertResponseContains(
            [
                'data' => ['type' => 'products', 'id' => '<toString(@product-1->id)>']
            ],
            $response
        );
    }

    public function testGetSubresourceForUnit()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'productunitprecisions',
                'id'          => '<toString(@product_unit_precision.product-1.milliliter->id)>',
                'association' => 'unit'
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'productunits',
                    'id'         => '<toString(@product_unit.milliliter->code)>',
                    'attributes' => [
                        'label' => 'milliliter'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForUnit()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'productunitprecisions',
                'id'          => '<toString(@product_unit_precision.product-1.milliliter->id)>',
                'association' => 'unit'
            ]
        );

        $this->assertResponseContains(
            [
                'data' => ['type' => 'productunits', 'id' => '<toString(@product_unit.milliliter->code)>']
            ],
            $response
        );
    }
}
