<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Api;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures\UpdateInventoryLevelsQuantities;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

/**
 * @group CommunityEdition
 */
class InventoryLevelApiTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([UpdateInventoryLevelsQuantities::class]);
    }

    /**
     * @param array $parameters
     * @param string $expectedDataFileName
     *
     * @dataProvider getListDataProvider
     */
    public function testGetList(array $parameters, $expectedDataFileName)
    {
        $response = $this->cget(['entity' => 'inventorylevels'], $parameters);
        $this->assertResponseContains($expectedDataFileName, $response);
    }

    /**
     * @return array
     */
    public function getListDataProvider()
    {
        return [
            'filter by Product' => [
                'parameters' => [
                    'include' => 'product,productUnitPrecision',
                    'filter' => [
                        'product.sku' => ['@product-1->sku'],
                    ]
                ],
                'expectedDataFileName' => 'filter_by_product.yml',
            ],
            'filter by Products' => [
                'parameters' => [
                    'include' => 'product,productUnitPrecision',
                    'filter' => [
                        'product.sku' => ['@product-1->sku', '@product-2->sku'],
                    ]
                ],
                'expectedDataFileName' => 'filter_by_products.yml',
            ],
            'filter by Products and Unit' => [
                'parameters' => [
                    'include' => 'product,productUnitPrecision',
                    'filter' => [
                        'product.sku' => ['@product-1->sku', '@product-2->sku'],
                        'productUnitPrecision.unit.code' => ['@product_unit.bottle->code'],
                    ]
                ],
                'expectedDataFileName' => 'filter_by_products_and_unit.yml',
            ],
            'filter by Products and Units' => [
                'parameters' => [
                    'include' => 'product,productUnitPrecision',
                    'filter' => [
                        'product.sku' => ['@product-1->sku', '@product-2->sku'],
                        'productUnitPrecision.unit.code' => ['@product_unit.bottle->code', '@product_unit.liter->code'],
                    ]
                ],
                'expectedDataFileName' => 'filter_by_products and_units.yml',
            ],
        ];
    }

    public function testUpdateEntity()
    {
        /** @var InventoryLevel $inventoryLevel */
        $inventoryLevel = $this->getReference('inventory_level.product_unit_precision.product-1.liter');

        $response = $this->patch(
            ['entity' => 'inventorylevels', 'id' => $inventoryLevel->getId()],
            [
                'data' => [
                    'type' => 'inventorylevels',
                    'id' => (string)$inventoryLevel->getId(),
                    'attributes' => [
                        'quantity' => 17,
                    ],
                ]
            ]
        );

        $result = $this->jsonToArray($response->getContent());
        $this->assertUpdatedInventoryLevel($result, $inventoryLevel->getId(), 17);
    }

    public function testUpdateEntityWithDefaultUnit()
    {
        /** @var InventoryLevel $inventoryLevel */
        $inventoryLevel = $this->getReference('inventory_level.product_unit_precision.product-1.liter');
        $response = $this->patch(
            ['entity' => 'inventorylevels', 'id' => $inventoryLevel->getId()],
            [
                'data' => [
                    'type' => 'inventorylevels',
                    'id' => (string)$inventoryLevel->getId(),
                    'attributes' => [
                        'quantity' => 1,
                    ],
                ]
            ]
        );

        $result = $this->jsonToArray($response->getContent());
        $this->assertUpdatedInventoryLevel($result, $result['data']['id'], 1);
    }

    public function testCreateEntity()
    {
        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post', ['entity' => 'inventorylevels']),
            [
                'data' => [
                    'type' => 'inventorylevels',
                    'attributes' => ['quantity' => 100],
                    'relationships' => [
                        'product' => [
                            'data' => [
                                'type' => $this->getEntityType(Product::class),
                                'id' => 'product-3',
                            ],
                        ],
                        'unit' => [
                            'data' => [
                                'type' => $this->getEntityType(ProductUnitPrecision::class),
                                'id' => 'liter',
                            ],
                        ],
                    ]
                ]
            ]
        );

        $this->assertResponseStatusCodeEquals($response, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function testDeleteEntity()
    {
        /** @var InventoryLevel $inventoryLevel */
        $inventoryLevel = $this->getReference('inventory_level.product_unit_precision.product-1.bottle');

        $response = $this->request(
            'DELETE',
            $this->getUrl('oro_rest_api_delete', ['entity' => 'inventorylevels', 'id' => $inventoryLevel->getId()])
        );

        $this->assertResponseStatusCodeEquals($response, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * @param array $result
     * @param int $inventoryLevelId
     * @param int $quantity
     */
    protected function assertUpdatedInventoryLevel(array $result, $inventoryLevelId, $quantity)
    {
        $inventoryLevel = $this->getEntityManager()->find(InventoryLevel::class, $inventoryLevelId);

        $this->assertEquals($quantity, $result['data']['attributes']['quantity']);
        $this->assertEquals($quantity, $inventoryLevel->getQuantity());
    }
}
