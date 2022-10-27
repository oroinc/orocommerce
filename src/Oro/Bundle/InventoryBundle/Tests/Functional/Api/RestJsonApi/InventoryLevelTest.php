<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures\UpdateInventoryLevelsQuantities;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

/**
 * @group CommunityEdition
 */
class InventoryLevelTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            LoadOrganization::class,
            LoadBusinessUnit::class,
            UpdateInventoryLevelsQuantities::class
        ]);
    }

    public function testGetListFilteredByOneProduct()
    {
        $response = $this->cget(
            ['entity' => 'inventorylevels'],
            [
                'include' => 'product,productUnitPrecision',
                'filter'  => [
                    'product' => ['@product-1->id']
                ]
            ]
        );

        $this->assertResponseContains('filter_by_product.yml', $response);
    }

    public function testGetListFilteredBySeveralProducts()
    {
        $response = $this->cget(
            ['entity' => 'inventorylevels'],
            [
                'include' => 'product,productUnitPrecision',
                'filter'  => [
                    'product' => ['@product-1->id', '@product-2->id']
                ]
            ]
        );

        $this->assertResponseContains('filter_by_products.yml', $response);
    }

    public function testUpdate()
    {
        $inventoryLevelId = $this->getReference('inventory_level.product_unit_precision.product-1.liter')->getId();

        $data = [
            'data' => [
                'type'       => 'inventorylevels',
                'id'         => (string)$inventoryLevelId,
                'attributes' => [
                    'quantity' => 17
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'inventorylevels', 'id' => (string)$inventoryLevelId],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['attributes']['quantity'] = '17';
        $this->assertResponseContains($expectedData, $response);

        $inventoryLevel = $this->getEntityManager()
            ->find(InventoryLevel::class, $inventoryLevelId);
        self::assertEquals(17, $inventoryLevel->getQuantity());
    }

    public function testTryToUpdateReadOnlyFields()
    {
        /** @var InventoryLevel $inventoryLevel */
        $inventoryLevel = $this->getReference('inventory_level.product_unit_precision.product-1.liter');
        $inventoryLevelId = $inventoryLevel->getId();
        $productId = $inventoryLevel->getProduct()->getId();
        $productUnitPrecisionId = $inventoryLevel->getProductUnitPrecision()->getId();
        $organizationId = $inventoryLevel->getOrganization()->getId();

        $data = [
            'data' => [
                'type'          => 'inventorylevels',
                'id'            => (string)$inventoryLevelId,
                'relationships' => [
                    'product'              => [
                        'data' => [
                            'type' => 'products',
                            'id'   => '<toString(@product-2->id)>'
                        ]
                    ],
                    'productUnitPrecision' => [
                        'data' => [
                            'type' => 'productunitprecisions',
                            'id'   => '<toString(@product_unit_precision.product-1.bottle->id)>'
                        ]
                    ]
                ]
            ]
        ];

        $this->patch(
            ['entity' => 'inventorylevels', 'id' => (string)$inventoryLevelId],
            $data
        );

        $inventoryLevel = $this->getEntityManager()
            ->find(InventoryLevel::class, $inventoryLevelId);
        self::assertEquals($productId, $inventoryLevel->getProduct()->getId());
        self::assertEquals($productUnitPrecisionId, $inventoryLevel->getProductUnitPrecision()->getId());
        self::assertEquals($organizationId, $inventoryLevel->getOrganization()->getId());
    }

    public function testTryToUpdateRelationshipProduct()
    {
        $response = $this->patchRelationship(
            [
                'entity'      => 'inventorylevels',
                'id'          => '<toString(inventory_level.product_unit_precision.product-1.liter->id)>',
                'association' => 'product'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdateRelationshipProductUnitPrecision()
    {
        $response = $this->patchRelationship(
            [
                'entity'      => 'inventorylevels',
                'id'          => '<toString(inventory_level.product_unit_precision.product-1.liter->id)>',
                'association' => 'productUnitPrecision'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
