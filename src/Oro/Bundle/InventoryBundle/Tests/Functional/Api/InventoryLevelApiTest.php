<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Api;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures\UpdateInventoryLevelsQuantities;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Symfony\Component\HttpFoundation\Response;

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
                        'product' => ['@product-1->id'],
                    ]
                ],
                'expectedDataFileName' => 'filter_by_product.yml',
            ],
            'filter by Products' => [
                'parameters' => [
                    'include' => 'product,productUnitPrecision',
                    'filter' => [
                        'product' => ['@product-1->id', '@product-2->id'],
                    ]
                ],
                'expectedDataFileName' => 'filter_by_products.yml',
            ]
        ];
    }

    public function testUpdateEntity()
    {
        /** @var InventoryLevel $inventoryLevel */
        $inventoryLevel = $this->getReference('inventory_level.product_unit_precision.product-1.liter');

        $response = $this->patch(
            ['entity' => 'inventorylevels', 'id' => (string) $inventoryLevel->getId()],
            [
                'data' => [
                    'type' => 'inventorylevels',
                    'id' => (string) $inventoryLevel->getId(),
                    'attributes' => [
                        'quantity' => 17
                    ],
                ]
            ]
        );

        $result = $this->jsonToArray($response->getContent());
        $this->assertUpdatedInventoryLevel($result, $inventoryLevel->getId(), 17);
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
