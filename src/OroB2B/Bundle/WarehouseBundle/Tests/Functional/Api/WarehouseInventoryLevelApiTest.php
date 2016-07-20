<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Functional\Api;

use Symfony\Component\HttpFoundation\Response;

use Doctrine\ORM\EntityManagerInterface;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;
use OroB2B\Bundle\WarehouseBundle\Tests\Functional\DataFixtures\LoadWarehousesAndInventoryLevels;
use OroB2B\Bundle\WarehouseBundle\Tests\Functional\DataFixtures\LoadWarehousesInventoryLevelWithPrimaryUnit;

/**
 * @dbIsolation
 */
class WarehouseInventoryLevelApiTest extends RestJsonApiTestCase
{
    const ARRAY_DELIMITER = ',';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(
            [
                LoadWarehousesInventoryLevelWithPrimaryUnit::class
            ]
        );
    }

    /**
     * @param string $entityClass
     * @param string $expectedStatusCode
     * @param array $params
     * @param array $filters
     * @param int $expectedCount
     * @param array $expectedContent
     *
     * @dataProvider cgetParamsAndExpectation
     */
    public function testCgetEntity(
        $entityClass,
        $expectedStatusCode,
        array $params,
        array $filters,
        $expectedCount,
        array $expectedContent = null
    ) {
        $entityType = $this->getEntityType($entityClass);

        foreach ($filters as $filter) {
            $filterValue = '';
            foreach ($filter['references'] as $value) {
                $method = $filter['method'];
                $filterValue .= $this->getReference($value)->$method() . self::ARRAY_DELIMITER;
            }
            $params['filter'][$filter['key']] = substr($filterValue, 0, -1);
        }

        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_cget', ['entity' => $entityType]),
            $params
        );

        $this->assertApiResponseStatusCodeEquals($response, $expectedStatusCode, $entityType, 'get list');
        $content = json_decode($response->getContent(), true);
        $this->assertCount($expectedCount, $content['data']);

        if ($expectedContent) {
            $expectedContent = $this->addReferenceRelationships($expectedContent);
            $this->assertIsContained($expectedContent, $content['data']);
        }
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function cgetParamsAndExpectation()
    {
        return [
            'filter by Product' => [
                'entityClass' => WarehouseInventoryLevel::class,
                'statusCode' => 200,
                'params' => [],
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'product.sku',
                        'references' => ['product.1']
                    ],
                ],
                'expectedCount' => 3,
                'expectedContent' => [
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 10,
                            'productSku' => 'product.1',
                            'unit' => 'liter',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 99,
                            'productSku' => 'product.1',
                            'unit' => 'bottle',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 10,
                            'productSku' => 'product.1',
                            'unit' => 'milliliter',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'filter by Products' => [
                'entityClass' => WarehouseInventoryLevel::class,
                'statusCode' => 200,
                'params' => [],
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'product.sku',
                        'references' => ['product.1', 'product.2']
                    ],
                ],
                'expectedCount' => 7,
                'expectedContent' => [
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 10,
                            'productSku' => 'product.1',
                            'unit' => 'liter',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 99,
                            'productSku' => 'product.1',
                            'unit' => 'bottle',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 12.345,
                            'productSku' => 'product.2',
                            'unit' => 'liter',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 98,
                            'productSku' => 'product.2',
                            'unit' => 'bottle',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 42,
                            'productSku' => 'product.2',
                            'unit' => 'box',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 98.765,
                            'productSku' => 'product.2',
                            'unit' => 'box',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE2,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 10,
                            'productSku' => 'product.1',
                            'unit' => 'milliliter',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'filter by Products and Warehouse' => [
                'entityClass' => WarehouseInventoryLevel::class,
                'statusCode' => 200,
                'params' => [],
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'product.sku',
                        'references' => ['product.1', 'product.2']
                    ],
                    [
                        'method' => 'getId',
                        'key' => 'warehouse.id',
                        'references' => [LoadWarehousesAndInventoryLevels::WAREHOUSE2]
                    ],
                ],
                'expectedCount' => 1,
                'expectedContent' => [
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 98.765,
                            'productSku' => 'product.2',
                            'unit' => 'box',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE2,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'filter by Products and Unit' => [
                'entityClass' => WarehouseInventoryLevel::class,
                'statusCode' => 200,
                'params' => [],
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'product.sku',
                        'references' => ['product.1', 'product.2']
                    ],
                    [
                        'method' => 'getCode',
                        'key' => 'productUnitPrecision.unit.code',
                        'references' => ['product_unit.bottle']
                    ],
                ],
                'expectedCount' => 2,
                'expectedContent' => [
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 99,
                            'productSku' => 'product.1',
                            'unit' => 'bottle',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 98,
                            'productSku' => 'product.2',
                            'unit' => 'bottle',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'filter by Products and Units' => [
                'entityClass' => WarehouseInventoryLevel::class,
                'statusCode' => 200,
                'params' => [],
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'product.sku',
                        'references' => ['product.1', 'product.2']
                    ],
                    [
                        'method' => 'getCode',
                        'key' => 'productUnitPrecision.unit.code',
                        'references' => ['product_unit.bottle', 'product_unit.liter']
                    ],
                ],
                'expectedCount' => 4,
                'expectedContent' => [
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 10,
                            'productSku' => 'product.1',
                            'unit' => 'liter',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 99,
                            'productSku' => 'product.1',
                            'unit' => 'bottle',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 12.345,
                            'productSku' => 'product.2',
                            'unit' => 'liter',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 98,
                            'productSku' => 'product.2',
                            'unit' => 'bottle',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'filter by Products, Warehouse and Unit' => [
                'entityClass' => WarehouseInventoryLevel::class,
                'statusCode' => 200,
                'params' => [],
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'product.sku',
                        'references' => ['product.1', 'product.2']
                    ],
                    [
                        'method' => 'getId',
                        'key' => 'warehouse.id',
                        'references' => [LoadWarehousesAndInventoryLevels::WAREHOUSE1]
                    ],
                    [
                        'method' => 'getCode',
                        'key' => 'productUnitPrecision.unit.code',
                        'references' => ['product_unit.liter']
                    ],
                ],
                'expectedCount' => 2,
                'expectedContent' => [
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 10,
                            'productSku' => 'product.1',
                            'unit' => 'liter',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 12.345,
                            'productSku' => 'product.2',
                            'unit' => 'liter',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'filter by Products, Warehouse and Units' => [
                'entityClass' => WarehouseInventoryLevel::class,
                'statusCode' => 200,
                'params' => [],
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'product.sku',
                        'references' => ['product.1', 'product.2']
                    ],
                    [
                        'method' => 'getId',
                        'key' => 'warehouse.id',
                        'references' => [LoadWarehousesAndInventoryLevels::WAREHOUSE1]
                    ],
                    [
                        'method' => 'getCode',
                        'key' => 'productUnitPrecision.unit.code',
                        'references' => ['product_unit.liter', 'product_unit.bottle']
                    ],
                ],
                'expectedCount' => 4,
                'expectedContent' => [
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 10,
                            'productSku' => 'product.1',
                            'unit' => 'liter',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 99,
                            'productSku' => 'product.1',
                            'unit' => 'bottle',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 12.345,
                            'productSku' => 'product.2',
                            'unit' => 'liter',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 98,
                            'productSku' => 'product.2',
                            'unit' => 'bottle',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'filter by Products, Warehouses and Units' => [
                'entityClass' => WarehouseInventoryLevel::class,
                'statusCode' => 200,
                'params' => [],
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'product.sku',
                        'references' => ['product.1', 'product.2']
                    ],
                    [
                        'method' => 'getId',
                        'key' => 'warehouse',
                        'references' => [
                            LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                            LoadWarehousesAndInventoryLevels::WAREHOUSE2
                        ]
                    ],
                    [
                        'method' => 'getCode',
                        'key' => 'productUnitPrecision.unit.code',
                        'references' => ['product_unit.liter', 'product_unit.bottle', 'product_unit.box']
                    ],
                ],
                'expectedCount' => 6,
                'expectedContent' => [
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 10,
                            'productSku' => 'product.1',
                            'unit' => 'liter',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 99,
                            'productSku' => 'product.1',
                            'unit' => 'bottle',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 12.345,
                            'productSku' => 'product.2',
                            'unit' => 'liter',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 98,
                            'productSku' => 'product.2',
                            'unit' => 'bottle',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 42,
                            'productSku' => 'product.2',
                            'unit' => 'box',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 98.765,
                            'productSku' => 'product.2',
                            'unit' => 'box',
                        ],
                        'relationships' => [
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE2,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testUpdateEntity()
    {
        /** @var WarehouseInventoryLevel $inventoryLevel */
        $inventoryLevel = $this->getReference(
            sprintf(
                'warehouse_inventory_level.%s.%s',
                LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                'product_unit_precision.product.1.liter'
            )
        );
        $this->assertEquals('10', $inventoryLevel->getQuantity());

        $entityType = $this->getEntityType(WarehouseInventoryLevel::class);
        $data = [
            'data' => [
                'type' => $entityType,
                'id' => $inventoryLevel->getProduct()->getSku(),
                'attributes' =>
                [
                    'quantity' => 17,
                    'unit' => $inventoryLevel->getProductUnitPrecision()->getProductUnitCode(),
                    'warehouse' => $inventoryLevel->getWarehouse()->getId(),
                ],
            ]
        ];
        $response = $this->request(
            'PATCH',
            $this->getUrl(
                'oro_rest_api_patch',
                ['entity' => $entityType, 'id' => $inventoryLevel->getProduct()->getSku()]
            ),
            $data
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        $result = self::jsonToArray($response->getContent());
        self::assertEquals(17, $result['data']['attributes']['quantity']);
    }

    public function testUpdateEntityWithDefaultUnit()
    {
        /** @var Warehouse $warehouse */
        $warehouse = $this->getReference(LoadWarehousesAndInventoryLevels::WAREHOUSE1);

        $entityType = $this->getEntityType(WarehouseInventoryLevel::class);
        $data = [
            'data' => [
                'type' => $entityType,
                'id' => 'product.1',
                'attributes' =>
                    [
                        'quantity' => 1,
                        'warehouse' => $warehouse->getId(),
                    ],
            ]
        ];
        $response = $this->request(
            'PATCH',
            $this->getUrl(
                'oro_rest_api_patch',
                ['entity' => $entityType, 'id' => 'product.1']
            ),
            $data
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        $result = self::jsonToArray($response->getContent());
        self::assertEquals(1, $result['data']['attributes']['quantity']);
    }

    public function testUpdateEntityWithOneWarehouse()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getReferenceRepository()->getManager();
        $em->remove($this->getReference(LoadWarehousesAndInventoryLevels::WAREHOUSE2));
        $em->flush();

        $entityType = $this->getEntityType(WarehouseInventoryLevel::class);
        $data = [
            'data' => [
                'type' => $entityType,
                'id' => 'product.1',
                'attributes' =>
                    [
                        'quantity' => 100,
                    ],
            ]
        ];
        $response = $this->request(
            'PATCH',
            $this->getUrl(
                'oro_rest_api_patch',
                ['entity' => $entityType, 'id' => 'product.1']
            ),
            $data
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        $result = self::jsonToArray($response->getContent());
        self::assertEquals(100, $result['data']['attributes']['quantity']);
    }

    /**
     * @param array $expectedContent
     * @return array
     */
    protected function addReferenceRelationships(array $expectedContent)
    {
        foreach ($expectedContent as $key => $expected) {
            if (array_key_exists('relationships', $expected)) {
                $relationships = [];
                foreach ($expected['relationships'] as $relationshipKey => $relationship) {
                    if (array_key_exists('references', $relationship)) {
                        foreach ($relationship['references'] as$reference) {
                            $method = $reference['method'];
                            $referenceId = $reference['reference'];
                            $relationship['data'][$reference['key']] = $this->getReference($referenceId)->$method();
                        }
                        unset($relationship['references']);
                    }
                    $relationships[$relationshipKey] = $relationship;
                }
                $expectedContent[$key]['relationships'] = $relationships;
            }
        }

        return $expectedContent;
    }

    /**
     * @param array $expected
     * @param array $content
     */
    protected function assertIsContained(array $expected, array $content)
    {
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $content);
            if (is_array($value)) {
                $this->assertIsContained($value, $content[$key]);
            } else {
                $this->assertEquals($value, $content[$key]);
            }
        }
    }
}
